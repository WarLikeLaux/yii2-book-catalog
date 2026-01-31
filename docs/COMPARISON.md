# Сравнение подходов (Yii2 MVC и Clean Architecture)

[← Назад в README](../README.md) • [→ Архитектурные решения](ARCHITECTURE.md)

Документ сравнивает три стилистики организации кода: классический Yii2 MVC, MVC с сервисным слоем и Clean Architecture, реализованную в этом проекте.

## 📌 Навигация

- [📊 Три уровня организации кода](#-три-уровня-организации-кода)
- [🔄 Пример: создание книги](#-пример-создание-книги)
- [📈 Сравнительная таблица](#-сравнительная-таблица)
- [🧩 Разбор паттернов (было → стало)](#-разбор-паттернов-было--стало)
  - [1. Form (отдельная валидация)](#1-form-отдельная-валидация)
  - [2. Command (чёткие данные)](#2-command-чёткие-данные)
  - [3. Mapper (преобразование)](#3-mapper-преобразование)
  - [4. Use Case (бизнес-логика)](#4-use-case-бизнес-логика)
  - [5. Repository (абстракция БД)](#5-repository-абстракция-бд)
  - [6. Value Object (доменные правила)](#6-value-object-доменные-правила)
  - [7. Domain Event (развязка)](#7-domain-event-развязка)
  - [8. Event Mapping (очереди)](#8-event-mapping-очереди)
  - [9. Queue (асинхронность)](#9-queue-асинхронность)
  - [10. Entity (Rich Domain Model)](#10-entity-rich-domain-model)
  - [11. Dependency Isolation (DI vs locator)](#11-dependency-isolation-di-vs-locator)
  - [12. Optimistic Locking (надежность)](#12-optimistic-locking-надежность)
  - [13. Command Pipeline (cross-cutting concerns)](#13-command-pipeline-cross-cutting-concerns)
  - [14. Handlers (слой представления)](#14-handlers-слой-представления)
  - [15. Validation Strategy (pragmatic approach)](#15-validation-strategy-pragmatic-approach)
  - [16. Specification (поиск и фильтрация)](#16-specification-поиск-и-фильтрация)
  - [17. Observability (tracing)](#17-observability-tracing)
  - [18. Разделение интерфейсов (ISP)](#18-разделение-интерфейсов-isp)
  - [19. Бесконечный скролл (HTMX)](#19-бесконечный-скролл-htmx)
- [🎯 Когда какой подход](#-когда-какой-подход)

---

## 📊 Три уровня организации кода

| Уровень | Подход              | Типичный проект                   |
| ------- | ------------------- | --------------------------------- |
| **1**   | Толстый контроллер  | Новичок, быстрый прототип         |
| **2**   | Контроллер + сервис | Большинство Yii2/Laravel проектов |
| **3**   | Clean Architecture  | Enterprise, сложная бизнес-логика |

[↑ К навигации](#-навигация)

---

## 🔄 Пример: создание книги

### Уровень 1: толстый контроллер

```php
// controllers/BookController.php
public function actionCreate()
{
    $model = new Book();

    if ($model->load(Yii::$app->request->post())) {
        // Загрузка файла
        $file = UploadedFile::getInstance($model, 'coverFile');
        if ($file) {
            $path = 'uploads/' . uniqid() . '.' . $file->extension;
            $file->saveAs(Yii::getAlias('@webroot/' . $path));
            $model->cover_url = '/' . $path;
        }

        // Валидация ISBN
        $isbn = str_replace(['-', ' '], '', $model->isbn);
        if (strlen($isbn) !== 13 || !ctype_digit($isbn)) {
            $model->addError('isbn', 'Неверный ISBN');
        }

        if (!$model->hasErrors() && $model->save()) {
            // Синхронизация авторов
            Yii::$app->db->createCommand()
                ->delete('book_authors', ['book_id' => $model->id])
                ->execute();
            foreach ($model->authorIds as $authorId) {
                Yii::$app->db->createCommand()->insert('book_authors', [
                    'book_id' => $model->id,
                    'author_id' => $authorId,
                ])->execute();
            }

            // Уведомления подписчикам
            $phones = Subscription::find()
                ->select('phone')
                ->where(['author_id' => $model->authorIds])
                ->column();
            foreach ($phones as $phone) {
                $sms = new SmsClient(Yii::$app->params['smsApiKey']);
                $sms->send($phone, "Новая книга: {$model->title}");
            }

            Yii::$app->session->setFlash('success', 'Книга создана');
            return $this->redirect(['view', 'id' => $model->id]);
        }
    }

    return $this->render('create', [
        'model' => $model,
        'authors' => ArrayHelper::map(Author::find()->all(), 'id', 'fio'),
    ]);
}
```

#### ✅ Плюсы:

- Быстро написать (30 минут)
- Всё в одном месте - легко найти
- Не нужно думать об архитектуре

#### ❌ Минусы:

- **60+ строк** в одном методе
- `actionUpdate` - копипаста с 80% совпадением
- SMS блокирует ответ страницы (100 подписчиков = 30 сек)
- Тесты: нужен Yii + база + файловая система + SMS API
- Поменял валидацию ISBN - трогаешь контроллер
- Поменял отправку SMS - трогаешь контроллер

---

### Уровень 2: контроллер + сервис

```php
// controllers/BookController.php
public function actionCreate()
{
    $model = new Book();

    if ($model->load(Yii::$app->request->post()) && $model->validate()) {
        $service = new BookService();
        $bookId = $service->create($model);

        if ($bookId) {
            Yii::$app->session->setFlash('success', 'Книга создана');
            return $this->redirect(['view', 'id' => $bookId]);
        }
    }

    return $this->render('create', [
        'model' => $model,
        'authors' => ArrayHelper::map(Author::find()->all(), 'id', 'fio'),
    ]);
}
```

```php
// services/BookService.php
class BookService
{
    public function create(Book $model): ?int
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $file = UploadedFile::getInstance($model, 'coverFile');
            if ($file) {
                $path = 'uploads/' . uniqid() . '.' . $file->extension;
                $file->saveAs(Yii::getAlias('@webroot/' . $path));
                $model->cover_url = '/' . $path;
            }

            if (!$model->save()) {
                throw new \Exception('Ошибка сохранения');
            }

            $this->syncAuthors($model->id, $model->authorIds);
            $transaction->commit();

            $this->notifySubscribers($model);

            return $model->id;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage());
            return null;
        }
    }

    private function syncAuthors(int $bookId, array $authorIds): void
    {
        // ... синхронизация
    }

    private function notifySubscribers(Book $model): void
    {
        // ... SMS
    }
}
```

#### ✅ Плюсы:

- Контроллер тонкий
- Логика переиспользуется
- Легче читать

#### ❌ Минусы:

- Сервис всё ещё зависит от `Book` (ActiveRecord)
- Сервис знает про `UploadedFile`, `Yii::$app`
- Тестирование всё ещё требует инфраструктуру
- SMS всё ещё блокирует запрос
- Сервис превращается в «толстый контроллер»

---

### Уровень 3: Clean Architecture (этот проект)

```php
// presentation/controllers/BookController.php
/**
 * @return string|Response|array<string, mixed>
 */
public function actionCreate(): string|Response|array
{
    $form = $this->itemViewFactory->createForm();

    if ($this->request->isPost && $form->loadFromRequest($this->request)) {
        if ($this->request->isAjax) {
            $this->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($form);
        }

        if ($form->validate()) {
            $bookId = $this->commandHandler->createBook($form);
            if ($bookId !== null) {
                return $this->redirect(['view', 'id' => $bookId]);
            }
        }
    }

    $authors = $this->viewDataFactory->getAuthorsList();

    return $this->render('create', [
        'model' => $form,
        'authors' => $authors,
    ]);
}
```

```php
// presentation/books/handlers/BookCommandHandler.php
public function createBook(BookForm $form): int|null
{
    try {
        $data = $this->prepareCommandData($form);
        /** @var CreateBookCommand $command */
        $command = $this->autoMapper->map($data, CreateBookCommand::class);
    } catch (\Throwable $e) {
        $this->addFormError($form, $e instanceof DomainException ? $e : new OperationFailedException(DomainErrorCode::MapperFailed, 400, $e));
        return null;
    }

    /** @var int|null */
    return $this->executeWithForm(
        $this->useCaseRunner,
        $form,
        $command,
        $this->createBookUseCase,
        Yii::t('app', 'book.success.created'),
    );
}
```

```php
// application/books/usecases/PublishBookUseCase.php
/**
 * @implements UseCaseInterface<PublishBookCommand, bool>
 */
final readonly class PublishBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionalEventPublisher $eventPublisher,
        private BookPublicationPolicy $publicationPolicy,
    ) {
    }

    /**
     * @param PublishBookCommand $command
     */
    public function execute(object $command): bool
    {
        /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
        assert($command instanceof PublishBookCommand);
        $book = $this->bookRepository->get($command->bookId);

        $book->publish($this->publicationPolicy);
        $this->bookRepository->save($book);

        $this->eventPublisher->publishAfterCommit(
            new BookPublishedEvent($command->bookId, $book->title, $book->year->value),
        );

        return true;
    }
}
```

```php
// domain/values/Isbn.php
final readonly class Isbn implements \Stringable
{
    private const array ISBN13_PREFIXES = ['978', '979'];

    public private(set) string $value;

    public function __construct(string $value)
    {
        $normalized = $this->normalizeIsbn($value);

        if (!$this->isValidIsbn($normalized)) {
            throw new ValidationException(DomainErrorCode::IsbnInvalidFormat);
        }

        $this->value = $normalized;
    }
}
```

#### ✅ Плюсы:

- Use Case не знает про Yii
- Тестируется изолированно
- SMS уходят в очередь
- Value Object гарантирует валидность
- Каждый класс имеет одну ответственность

#### ❌ Минусы:

- Больше файлов на операцию
- Выше порог входа
- Избыточно для простых CRUD

[↑ К навигации](#-навигация)

---

## 📈 Сравнительная таблица

| Критерий                    | Толстый контроллер | +Сервис                     | Clean Architecture               |
| --------------------------- | ------------------ | --------------------------- | -------------------------------- |
| **Время разработки**        | ⚡ 30 мин          | ⚡ 1 час                    | 🐢 3-4 часа                      |
| **Файлов на операцию**      | 1                  | 2                           | 6-8                              |
| **Строк кода**              | 60 в одном         | 15 + 80                     | 15 + 20 + 25 + ...               |
| **Unit-тесты**              | ❌ Невозможно      | ⚠️ Сложно                   | ✅ Легко                         |
| **Покрытие тестами**        | 0-10%              | 10-30%                      | 100%                             |
| **SMS блокирует**           | ✅ Да              | ✅ Да                       | ❌ Нет (очередь)                 |
| **Зависимость от Yii**      | 🔴 Везде           | 🟡 В сервисе                | 🟢 Infrastructure + Presentation |
| **Изменить провайдера SMS** | Правим контроллер  | Правим сервис               | Новый адаптер                    |
| **Копипаста Create/Update** | 80%                | 50%                         | 10%                              |
| **Правила домена**          | В контроллере      | В сервисе                   | Entity/Policy                    |
| **Поиск/фильтрация**        | AR в контроллере   | AR в сервисе                | Specifications + Query Service   |
| **Маппинг данных**          | Ручной             | Ручной                      | AutoMapper (атрибуты)            |
| **Гидрация сущностей**      | Свойства AR        | ActiveRecord::setAttributes | ActiveRecordHydrator             |
| **Хранилище файлов**        | `uploads/`         | `uploads/`                  | CAS (контентно-адресуемое)       |
| **Поддержка через 2 года**  | 😱 Ад              | 😐 Норм                     | 😊 Легко                         |

[↑ К навигации](#-навигация)

---

## 🧩 Разбор паттернов (было → стало)

### 1. Form (отдельная валидация)

**Было (в модели Book):**

```php
class Book extends ActiveRecord
{
    public $coverFile;
    public $authorIds;

    public function rules()
    {
        return [
            ['title', 'string', 'max' => 255],
            ['coverFile', 'file', 'extensions' => 'png, jpg'],
            // + сценарии create/update
        ];
    }
}
```

❌ **Проблема:** модель смешивает хранение и валидацию ввода.

**Стало (BookForm):**

```php
// presentation/books/forms/BookForm.php
final class BookForm extends Model
{
    public function __construct(
        private readonly BookQueryServiceInterface $bookQueryService,
        private readonly AuthorQueryServiceInterface $authorQueryService,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    /** @var int|string|null */
    public $id;

    /** @var string */
    public $title = '';

    /** @var int|string|null */
    public $year;

    /** @var string|null */
    public $description;

    /** @var string|int|null */
    public $isbn = '';

    /** @var array<int>|string|null */
    public $authorIds = [];

    /** @var \yii\web\UploadedFile|string|null */
    public $cover;
}
```

✅ **Результат:** форма отвечает только за ввод, AR - только за persistence.

---

### 2. Command (чёткие данные)

**Было:**

```php
$service->create($model);  // Book? BookForm? Array?
```

❌ **Проблема:** непонятный контракт.

**Стало:**

```php
$command = new CreateBookCommand(
    title: 'Название',
    year: 2024,
    description: 'Короткое описание',
    isbn: '9783161484100',
    authorIds: [1, 2],
    cover: '/covers/123.png'
);
$useCase->execute($command);
```

✅ **Результат:** строгие типы и явные поля.

---

### 3. Mapper (преобразование)

**Было (в контроллере):**

```php
$command = new CreateBookCommand(
    $form->title,
    $form->year,
    $form->isbn,
    $form->authorIds,
    $coverUrl
);
```

❌ **Проблема:** копипаста маппинга.

**Стало:**

```php
$data = $this->prepareCommandData($form);
/** @var CreateBookCommand $command */
$command = $this->autoMapper->map($data, CreateBookCommand::class);
```

✅ **Результат:** единый маппинг и меньше рутины.

---

### 4. Use Case (бизнес-логика)

**Было:**

```php
public function actionCreate()
{
    // Внутри контроллера: бизнес-правила, SQL, файлы, SMS
}
```

❌ **Проблема:** бизнес-логика смешана с инфраструктурой.

**Стало:**

```php
// application/books/usecases/CreateBookUseCase.php
public function execute(object $command): int
{
    /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
    assert($command instanceof CreateBookCommand);
    $currentYear = (int) $this->clock->now()->format('Y');

    $cover = $command->cover;
    if (is_string($cover)) {
        $cover = new StoredFileReference($cover);
    }

    $book = Book::create(
        title: $command->title,
        year: new BookYear($command->year, $currentYear),
        isbn: new Isbn($command->isbn),
        description: $command->description,
        coverImage: $cover,
    );
    $book->replaceAuthors($command->authorIds);

    $this->bookRepository->save($book);
    $bookId = $book->id;

    if ($bookId === null) {
        throw new RuntimeException('Failed to retrieve book ID after save');
    }

    return $bookId;
}
```

✅ **Результат:** бизнес-логика сосредоточена в Use Case.

---

### 5. Repository (абстракция БД)

**Было:**

```php
Book::find()->where(['id' => $id])->one();
```

❌ **Проблема:** зависимость домена от AR.

**Стало:**

```php
// application/ports/BookRepositoryInterface.php
interface BookRepositoryInterface
{
    public function save(Book $book): void;
    public function get(int $id): Book;
    public function getByIdAndVersion(int $id, int $expectedVersion): Book;
    public function delete(Book $book): void;
}
```

```php
// infrastructure/repositories/BookRepository.php
public function save(BookEntity $book): void
{
    $isNew = $book->id === null;
    $ar = $isNew ? new Book() : $this->getArForEntity($book, Book::class, DomainErrorCode::BookNotFound);
    $ar->version = $book->version;

    $this->hydrator->hydrate($ar, $book, [
        'title',
        'year',
        'isbn',
        'description',
        'cover_url' => static fn(BookEntity $e): ?string => $e->coverImage?->getPath(),
        'is_published' => static fn(BookEntity $e): int => $e->published ? 1 : 0,
    ]);

    $this->persist($ar, DomainErrorCode::BookIsbnExists);
}
```

✅ **Результат:** домен не знает о БД, инфраструктура скрыта.

---

### 6. Value Object (доменные правила)

**Было:**

```php
if (strlen($isbn) !== 13 || !ctype_digit($isbn)) {
    $model->addError('isbn', 'Неверный ISBN');
}
```

**Стало:**

```php
final readonly class Isbn implements \Stringable
{
    private const array ISBN13_PREFIXES = ['978', '979'];

    public private(set) string $value;

    public function __construct(string $value)
    {
        $normalized = $this->normalizeIsbn($value);

        if (!$this->isValidIsbn($normalized)) {
            throw new ValidationException(DomainErrorCode::IsbnInvalidFormat);
        }

        $this->value = $normalized;
    }
}
```

✅ **Результат:** невозможно создать невалидный ISBN.

---

### 7. Domain Event (развязка)

**Было:**

```php
Yii::$app->queue->push(new NotifyJob($bookId));
```

❌ **Проблема:** бизнес-логика знает про очередь.

**Стало:**

```php
// domain/events/BookPublishedEvent.php
final readonly class BookPublishedEvent implements QueueableEvent
{
    public const string EVENT_TYPE = 'book.published';

    public function __construct(
        public int $bookId,
        public string $title,
        public int $year,
    ) {
    }
}
```

```php
$this->eventPublisher->publishAfterCommit(
    new BookPublishedEvent($command->bookId, $book->title, $book->year->value),
);
```

✅ **Результат:** домен публикует событие, инфраструктура решает как обрабатывать.

---

### 8. Event Mapping (очереди)

**Было:**

```php
if ($event instanceof BookPublishedEvent) {
    Yii::$app->queue->push(new NotifySubscribersJob(...));
}
```

❌ **Проблема:** условная логика разрастается.

**Стало:**

```php
// config/container/adapters.php
EventJobMappingRegistry::class => static fn(): EventJobMappingRegistry => new EventJobMappingRegistry([
    BookPublishedEvent::class => NotifySubscribersJob::class,
]),
```

✅ **Результат:** маппинг событий централизован в конфигурации.

---

### 9. Queue (асинхронность)

**Было:**

```php
foreach ($subscribers as $sub) {
    $sms->send($sub->phone, ...);
}
```

❌ **Проблема:** страница ждёт отправку.

**Стало:**

```php
// infrastructure/queue/handlers/NotifySubscribersHandler.php
public function handle(int $bookId, string $title, Queue $queue): void
{
    $message = $this->translator->translate('app', 'notification.book.released', ['title' => $title]);

    foreach ($this->queryService->getSubscriberPhonesForBook($bookId) as $phone) {
        $queue->push(new NotifySingleSubscriberJob(
            $phone,
            $message,
            $bookId,
        ));
    }
}
```

✅ **Результат:** fan-out в фоне, UI отвечает мгновенно.

---

### 10. Entity (Rich Domain Model)

**Было:**

```php
class Book extends ActiveRecord
{
    public function publish(): void
    {
        $this->status = 'published';
        $this->save();
    }
}
```

**Стало:**

```php
// domain/entities/Book.php
final class Book
{
    public function publish(BookPublicationPolicy $policy): void
    {
        $policy->ensureCanPublish($this);
        $this->published = true;
    }

    public function addAuthor(int $authorId): void
    {
        if ($authorId <= 0) {
            throw new ValidationException(DomainErrorCode::BookInvalidAuthorId);
        }

        if (in_array($authorId, $this->authorIds, true)) {
            return;
        }

        $this->authorIds[] = $authorId;
    }
}
```

✅ **Результат:** сущность чистая и тестируемая.

---

### 11. Dependency Isolation (DI vs locator)

**Было:**

```php
Yii::$app->db->createCommand(...);
Yii::$app->queue->push(...);
```

**Стало:**

```php
final readonly class PublishBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private TransactionalEventPublisher $eventPublisher,
        private BookPublicationPolicy $publicationPolicy,
    ) {
    }
}
```

✅ **Результат:** зависимости передаются через конструкторы, а Use Case не знает про Yii.

---

### 12. Optimistic Locking (надежность)

**Было:**

```php
// Потеря обновлений при параллельной записи
```

**Стало:**

```php
// infrastructure/persistence/Book.php
public function behaviors(): array
{
    return [
        [
            'class' => OptimisticLockBehavior::class,
            'value' => fn(): int => $this->version ?? 1,
        ],
    ];
}

public function optimisticLock(): string
{
    return 'version';
}
```

```php
// infrastructure/repositories/BookRepository.php
$ar->version = $book->version;
$this->persist($ar, DomainErrorCode::BookIsbnExists);
```

✅ **Результат:** конфликт версий ловится и превращается в доменное исключение.

---

### 13. Command Pipeline (cross-cutting concerns)

**Было:**

```php
public function create(Book $model)
{
    $transaction = Yii::$app->db->beginTransaction();
    try {
        $this->tracer->start('create_book');
        // бизнес-логика...
        $transaction->commit();
    } catch (\Throwable $e) {
        $transaction->rollBack();
        throw $e;
    }
}
```

**Стало:**

```php
// application/common/pipeline/PipelineFactory.php
public function createDefault(): PipelineInterface
{
    return (new Pipeline())
        ->pipe(new TracingMiddleware($this->tracer))
        ->pipe(new IdempotencyMiddleware($this->idempotencyService))
        ->pipe(new TransactionMiddleware($this->transaction));
}
```

```php
// presentation/common/services/WebUseCaseRunner.php
$result = $this->pipelineFactory->createDefault()->execute($command, $useCase);
```

✅ **Результат:** сквозные аспекты вынесены в middleware.

---

### 14. Handlers (слой представления)

**Было:**

```php
// Контроллер делает всё
```

**Стало:**

```php
// presentation/books/handlers/BookCommandHandler.php
public function createBook(BookForm $form): int|null
{
    try {
        $data = $this->prepareCommandData($form);
        /** @var CreateBookCommand $command */
        $command = $this->autoMapper->map($data, CreateBookCommand::class);
    } catch (\Throwable $e) {
        $this->addFormError($form, $e instanceof DomainException ? $e : new OperationFailedException(DomainErrorCode::MapperFailed, 400, $e));
        return null;
    }

    /** @var int|null */
    return $this->executeWithForm(
        $this->useCaseRunner,
        $form,
        $command,
        $this->createBookUseCase,
        Yii::t('app', 'book.success.created'),
    );
}
```

✅ **Результат:** контроллер остаётся координатором, а Handler концентрирует логику команды.

---

### 15. Validation Strategy (pragmatic approach)

**Было (ActiveRecord rules):**

```php
[['isbn'], 'unique'],
```

**Стало:**

```php
// presentation/books/forms/BookForm.php
public function validateIsbnUnique(string $attribute): void
{
    $value = $this->$attribute;

    if (!is_string($value)) {
        return;
    }

    $excludeId = $this->id !== null ? (int)$this->id : null;

    if (!$this->bookQueryService->existsByIsbn($value, $excludeId)) {
        return;
    }

    $this->addError($attribute, Yii::t('app', 'book.error.isbn_exists'));
}
```

```php
// infrastructure/repositories/BaseActiveRecordRepository.php
protected function persist(ActiveRecord $model, ?DomainErrorCode $duplicateError = null): void
{
    try {
        if (!$model->save(false)) {
            throw new OperationFailedException(DomainErrorCode::EntityPersistFailed);
        }
    } catch (IntegrityException $e) {
        if ($this->isDuplicateError($e)) {
            if ($duplicateError instanceof DomainErrorCode) {
                throw new AlreadyExistsException($duplicateError, 409, $e);
            }

            throw new AlreadyExistsException(previous: $e);
        }

        throw $e;
    }
}
```

✅ **Результат:** форма даёт быстрый фидбек, а БД гарантирует целостность.

---

### 16. Specification (поиск и фильтрация)

**Было:**

```php
return Book::find()
    ->where(['year' => $year])
    ->andWhere(['like', 'title', $term])
    ->all();
```

**Стало:**

```php
// domain/specifications/BookSearchSpecificationFactory.php
$specification = $factory->createFromSearchTerm($term);

// infrastructure/queries/BookQueryService.php
$visitor = new ActiveQueryBookSpecificationVisitor($query, $this->db);
$specification->accept($visitor);
```

✅ **Результат:** критерии в домене, SQL остаётся в инфраструктуре.

---

### 17. Observability (tracing)

**Было:**

```php
// Логи разбросаны по проекту
```

**Стало:**

```php
// infrastructure/adapters/decorators/QueueTracingDecorator.php
final readonly class QueueTracingDecorator implements QueueInterface
{
    public function __construct(
        private QueueInterface $queue,
        private TracerInterface $tracer,
    ) {
    }

    public function push(object $job): void
    {
        $this->tracer->trace(
            'Queue::' . __FUNCTION__,
            fn() => $this->queue->push($job),
            ['job_class' => $job::class],
        );
    }
}
```

✅ **Результат:** трассировка добавляется без изменения бизнес-кода.

---

### 18. Разделение интерфейсов (ISP)

**Было:**

```php
interface BookRepositoryInterface {
    public function save(Book $book): void;
    public function get(int $id): Book;
    public function search(string $term): array;
}
```

**Стало:**

```php
interface BookRepositoryInterface
{
    public function save(Book $book): void;
    public function get(int $id): Book;
    public function delete(Book $book): void;
}

interface BookFinderInterface
{
    public function findById(int $id): ?BookReadDto;
    public function findByIdWithAuthors(int $id): ?BookReadDto;
}

interface BookSearcherInterface
{
    public function search(string $term, int $page, int $pageSize): PagedResultInterface;
}
```

✅ **Результат:** зависимости Use Cases ограничены нужными методами.

---

### 19. Бесконечный скролл (HTMX)

**Было:**

- Перезагрузка страницы на `?page=2`
- Или jQuery-логика с ручным DOM-апдейтом

**Стало:**

```html
<div
  hx-get="/site/index?page=2"
  hx-target="#book-cards-container"
  hx-swap="beforeend"
  hx-trigger="revealed"
  hx-select="#book-cards-container > .col-md-4, #load-more-container"
  hx-select-oob="#load-more-container"
></div>
```

✅ **Результат:** бесшовная подгрузка без тяжёлого JS.

[↑ К навигации](#-навигация)

---

## 🎯 Когда какой подход

| Ситуация                       | Рекомендация        |
| ------------------------------ | ------------------- |
| Прототип за 2 часа             | Толстый контроллер  |
| Типичный проект (1-2 дева)     | Контроллер + сервис |
| Сложная бизнес-логика          | Clean Architecture  |
| Нужны тесты                    | Clean Architecture  |
| Интеграции (SMS, Payment, API) | Clean Architecture  |
| 3+ разработчика                | Clean Architecture  |
| Проект на 2+ года              | Clean Architecture  |

[↑ К навигации](#-навигация)
