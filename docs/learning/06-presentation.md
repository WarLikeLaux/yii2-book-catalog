# Presentation layer — тонкий контроллер

[← Назад к оглавлению](../learning.md)

---

Presentation принимает HTTP-запросы, валидирует ввод, вызывает Application layer и формирует ответ. Здесь Yii2 используется по полной: контроллеры, формы, ActiveForm, виджеты.

## Контроллер

Контроллер — координатор. Не содержит бизнес-логики:

```php
// src/presentation/controllers/BookController.php
public function actionCreate(): string|Response
{
    $form = $this->itemViewFactory->createForm();

    if (!$this->request->isPost || !$form->loadFromRequest($this->request)) {
        return $this->renderCreateForm($form);
    }

    if ($this->request->isAjax) {
        return $this->asJson(ActiveForm::validate($form));
    }

    if (!$form->validate()) {
        return $this->renderCreateForm($form);
    }

    try {
        $bookId = $this->commandHandler->createBook($form);
        return $this->redirect(['view', 'id' => $bookId]);
    } catch (ApplicationException $e) {
        $this->addFormError($form, $e);
        return $this->renderCreateForm($form);
    }
}
```

Логика: загрузить форму → валидировать → вызвать handler → обработать ошибки. Никакой бизнес-логики — она в Use Case.

## Form

Форма — объект Yii2 Model, отвечающий только за валидацию ввода:

```php
// src/presentation/books/forms/BookForm.php
final class BookForm extends Model
{
    public $title = '';
    public $year;
    public $description;
    public $isbn = '';
    /** @var array<int>|string|null */
    public $authorIds = [];
    /** @var UploadedFile|string|null */
    public $cover;
    public int $version = 1;

    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn', 'authorIds'], 'required'],
            [['year'], 'integer', 'min' => 1000, 'max' => (int)date('Y') + 5],
            [['title'], 'string', 'max' => 255],
            [['isbn'], IsbnValidator::class],
            [['authorIds'], 'each', 'rule' => ['integer']],
            // ...
        ];
    }
}
```

Форма знает про `UploadedFile` и `Yii::$app->request`. Application layer об этом не знает — данные преобразуются через Mapper.

## CommandHandler

Связующее звено между Yii2 (формы, файлы) и Application (Use Cases):

```php
// src/presentation/books/handlers/BookCommandHandler.php
public function createBook(BookForm $form): int
{
    $cover = $this->operationRunner->runStep(
        fn(): ?string => $this->processCoverUpload($form),
        'Failed to upload book cover',
    );

    if ($form->cover instanceof UploadedFile && $cover === null) {
        throw new OperationFailedException(
            DomainErrorCode::FileStorageOperationFailed->value,
            field: 'cover',
        );
    }

    $command = $this->commandMapper->toCreateCommand($form, $cover);

    $result = $this->operationRunner->executeAndPropagate(
        $command,
        $this->createBookUseCase,
        Yii::t('app', 'book.success.created'),
    );
    assert(is_int($result));

    return $result;
}
```

Handler делает то, что Use Case делать не должен: обрабатывает `UploadedFile`, загружает файл в Storage, показывает flash-сообщения.

## CommandMapper

Превращает данные формы в Command DTO:

```php
// src/presentation/books/mappers/BookCommandMapper.php
public function toCreateCommand(BookForm $form, ?string $storedCover): CreateBookCommand
{
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
}
```

Маппер преобразует слабо типизированные данные формы (`$form->year` может быть строкой) в строго типизированный Command.

## ViewModel Pattern

Views получают типизированные ViewModel вместо массивов:

```php
// src/presentation/books/dto/BookViewViewModel.php
final readonly class BookViewViewModel implements ViewModelInterface
{
    public function __construct(
        public BookReadDto $book,
        public string $coverUrl,
        public string $statusLabel,
        public string $statusClass,
        // ...
    ) {}
}
```

View Factory создаёт ViewModel с подготовленными данными:

```php
// src/presentation/books/handlers/BookItemViewFactory.php
public function createViewViewModel(int $id): BookViewViewModel
{
    $book = $this->bookFinder->findByIdWithAuthors($id);

    if (!$book instanceof BookReadDto) {
        throw new EntityNotFoundException(DomainErrorCode::BookNotFound);
    }

    return new BookViewViewModel(
        book: $book,
        coverUrl: $this->urlResolver->resolve($book->coverUrl),
        statusLabel: $this->getStatusLabel($book->status),
        statusClass: $this->getStatusCssClass($book->status),
    );
}
```

View работает с ViewModel и не вызывает бизнес-логику:

```php
// src/presentation/books/views/view.php
/** @var BookViewViewModel $viewModel */
echo $viewModel->book->title;
echo $viewModel->coverUrl;
echo $viewModel->statusLabel;
```

## ViewModelRenderer

Базовый контроллер рендерит View через ViewModel:

```php
// src/presentation/common/ViewModelRenderer.php
final class ViewModelRenderer
{
    public function render(Controller $controller, string $view, ViewModelInterface $viewModel): string
    {
        return $controller->render($view, ['viewModel' => $viewModel]);
    }
}
```

View всегда получает одну переменную — `$viewModel`. Никаких `compact('book', 'authors', 'statusLabel')`.

## Idempotency Filter

Защита от дублей на уровне HTTP:

```php
// src/presentation/common/filters/IdempotencyFilter.php
public function beforeAction($action): bool
{
    $key = $this->extractKey($action);

    if ($key === null) {
        return true;
    }

    $result = $this->idempotencyService->check($key);

    if ($result->status === IdempotencyKeyStatus::Completed) {
        // Повторный запрос — вернуть сохранённый ответ
        $this->replayResponse($result);
        return false;
    }

    return true;
}
```

Пользователь нажал "Отправить" дважды — второй запрос возвращает результат первого, не дублируя операцию.

## REST API

Тот же Use Case отдаёт JSON через API-контроллер:

```php
// src/presentation/controllers/api/v1/BookController.php
#[OA\Get(
    path: '/api/v1/books/{id}',
    summary: 'Получить книгу по ID',
    // ...
)]
public function actionView(int $id): array
{
    $book = $this->bookFinder->findByIdWithAuthors($id);

    if (!$book instanceof BookReadDto) {
        throw new EntityNotFoundException(DomainErrorCode::BookNotFound);
    }

    return ApiResponse::success($book)->toArray();
}
```

Один Use Case, два Presentation: Web (HTML) и API (JSON). Бизнес-логика не дублируется.

## HTMX

Бесконечный скролл без JavaScript-фреймворка:

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

Контроллер определяет HTMX-запрос через `HtmxDetectionTrait` и рендерит частичный HTML вместо полной страницы.

## Итого

Presentation содержит тонкие контроллеры, формы (валидация ввода), CommandHandlers (связь формы с Use Case), Mappers (Form → Command), ViewModels (типизированные данные для View), фильтры (Idempotency, RateLimit) и два формата вывода (HTML + REST API).

---

[Далее: Поток данных →](07-request-lifecycle.md)
