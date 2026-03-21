# Поток данных: жизнь HTTP-запроса

[← Назад к оглавлению](../learning.md)

---

Сквозной пример: пользователь создаёт книгу. Отслеживаем данные от браузера до БД и обратно.

## 1. HTTP POST → Controller

Пользователь заполняет форму и нажимает "Создать".

```
POST /books/create
Content-Type: multipart/form-data

BookForm[title]=Чистая архитектура
BookForm[year]=2017
BookForm[isbn]=978-0-13-449416-6
BookForm[authorIds][]=1
BookForm[cover]=@cover.jpg
```

Контроллер принимает запрос:

```php
// BookController::actionCreate()
$form = $this->itemViewFactory->createForm();

if (!$this->request->isPost || !$form->loadFromRequest($this->request)) {
    return $this->renderCreateForm($form);
}

if (!$form->validate()) {
    return $this->renderCreateForm($form);
}
```

На этом этапе: `BookForm` загружен из POST, правила Yii2 проверены (обязательность, длина, формат ISBN). Если ошибка — рендерим форму с ошибками.

## 2. Controller → CommandHandler

Контроллер делегирует обработку:

```php
$bookId = $this->commandHandler->createBook($form);
```

## 3. CommandHandler: файл + маппинг

Handler обрабатывает то, что Application знать не должен — загрузку файла:

```php
// BookCommandHandler::createBook()
$cover = $this->operationRunner->runStep(
    fn(): ?string => $this->processCoverUpload($form),
    'Failed to upload book cover',
);
```

`processCoverUpload` берёт `UploadedFile`, загружает в Content-Addressable Storage, получает путь. Затем маппинг:

```php
$command = $this->commandMapper->toCreateCommand($form, $cover);
```

## 4. CommandMapper: Form → Command

```php
// BookCommandMapper::toCreateCommand()
return new CreateBookCommand(
    title: (string)$form->title,
    year: (int)$form->year,
    description: $form->description !== '' ? $form->description : null,
    isbn: (string)$form->isbn,
    authorIds: AuthorIdCollection::fromArray(
        is_array($form->authorIds) ? $form->authorIds : [],
    ),
    storedCover: $storedCover,
);
```

На выходе — `readonly` DTO без привязки к HTTP. Command не знает про `UploadedFile` и `Yii::$app->request`.

## 5. Pipeline: middleware

Handler вызывает Use Case через Pipeline:

```php
$result = $this->operationRunner->executeAndPropagate($command, $this->createBookUseCase, ...);
```

Внутри `WebOperationRunner`:

```php
$this->pipelineFactory->createDefault()->execute($command, $useCase);
```

Pipeline оборачивает вызов:
1. `DomainExceptionTranslationMiddleware` — ловит `DomainException`, маппит в `ApplicationException`
2. `TransactionMiddleware` — `BEGIN TRANSACTION` ... `COMMIT`

## 6. UseCase: бизнес-логика

```php
// CreateBookUseCase::execute()
$authorIds = $command->authorIds->toArray();

if ($this->bookIsbnChecker->existsByIsbn($command->isbn)) {
    throw new AlreadyExistsException(DomainErrorCode::BookIsbnExists);
}

if ($authorIds !== [] && !$this->authorExistenceChecker->existsAllByIds($authorIds)) {
    throw new EntityNotFoundException(DomainErrorCode::BookAuthorsNotFound);
}

$currentYear = (int) $this->clock->now()->format('Y');

$book = Book::create(
    title: $command->title,
    year: new BookYear($command->year, $currentYear),
    isbn: new Isbn($command->isbn),
    description: $command->description,
    coverImage: $coverImage,
);
$book->replaceAuthors($authorIds);

return $this->bookRepository->save($book);
```

На этом этапе:
- Проверена уникальность ISBN (через порт `BookIsbnCheckerInterface`)
- Проверено существование авторов (через порт `AuthorExistenceCheckerInterface`)
- Создана доменная сущность `Book` со статусом `Draft`
- Value Objects (`BookYear`, `Isbn`) провалидировали значения в конструкторах

## 7. Repository: Entity → AR → БД

```php
// BookRepository::save()
$model = new Book();
$model->version = $book->version;

$this->hydrator->hydrate($model, $book, [
    'title', 'year', 'isbn', 'description',
    'cover_url' => static fn(BookEntity $e): ?string => $e->coverImage?->getPath(),
    'status' => static fn(BookEntity $e): string => $e->status->value,
]);

$this->persist($model, DomainErrorCode::BookStaleData, DomainErrorCode::BookIsbnExists);
```

Hydrator перекладывает данные из Entity в ActiveRecord. `persist()` вызывает `$model->save(false)` с обработкой `StaleObjectException` и `IntegrityException`.

Далее:
- `assignIdentity($book, $model->id)` — присваивает ID сущности через Reflection
- `syncBookAuthors()` — синхронизирует связи book_authors
- `publishRecordedEvents()` — регистрирует события для публикации после коммита

## 8. Публикация событий

Книга создана со статусом `Draft` — событий нет. Но если позже вызвать `transitionTo(Published)`:

```
Book::transitionTo()
  → recordEvent(BookStatusChangedEvent)
    → Repository::publishRecordedEvents()
      → EventPublisher::publishAfterCommit()
        → COMMIT
        → EventToJobMapper::map(BookStatusChangedEvent)
          → NotifySubscribersJob → Queue
```

Событие публикуется **после** коммита транзакции. Job попадает в очередь. Страница уже отдана пользователю.

## 9. Ответ пользователю

```php
// BookController::actionCreate()
$bookId = $this->commandHandler->createBook($form);
return $this->redirect(['view', 'id' => $bookId]);
```

Пользователь видит страницу книги. Весь путь от POST до редиректа:

```
Browser POST
  → Controller (загрузить форму, валидировать)
    → CommandHandler (загрузить файл, преобразовать в Command)
      → Pipeline (трейсинг → маппинг ошибок → транзакция)
        → UseCase (проверки, создать Entity)
          → Repository (гидрация → AR → INSERT → events)
      → Pipeline (COMMIT → publish events)
    → CommandHandler (flash-сообщение)
  → Controller (redirect)
Browser GET /books/42
```

## Обработка ошибок

Если на любом этапе возникает ошибка:

| Этап | Ошибка | Что происходит |
|------|--------|----------------|
| Form.validate() | Невалидный ввод | Форма рендерится с ошибками |
| UseCase | ISBN дубликат | `DomainException` → `ApplicationException` → форма с ошибкой у поля isbn |
| UseCase | Авторы не найдены | `EntityNotFoundException` → ошибка у поля authorIds |
| Repository | Optimistic lock | `StaleDataException` → "Данные устарели, обновите страницу" |
| Repository | DB constraint | `IntegrityException` → `AlreadyExistsException` |
| Pipeline | Любое исключение | `TransactionMiddleware` → ROLLBACK |

Ошибки пробрасываются вверх по стеку. Каждый слой преобразует в свой тип: Domain → Application → Presentation.

---

[Далее: Dependency Injection →](08-dependency-injection.md)
