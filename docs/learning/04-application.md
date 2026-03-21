# Application layer — оркестрация

[← Назад к оглавлению](../learning.md)

---

Application layer координирует бизнес-логику. Получает команду, достаёт сущности, вызывает их методы, сохраняет результат. Чистый PHP — без зависимостей от Yii2.

> **Если ты привык к Yii2:** Application layer — это то, что в классическом Yii2 живёт в `BookService`. Разница в трёх вещах:
> 1. Use Case не знает про `Yii::$app`, `UploadedFile`, `ActiveRecord`. Зависит только от интерфейсов.
> 2. Входные данные — не модель формы, а типизированный Command DTO.
> 3. Транзакции, трейсинг, маппинг ошибок — не в каждом методе, а один раз в Pipeline.
>
> Зачем? Потому что `BookService::create(Book $model)` принимает ActiveRecord, а значит зависит от Yii2 и не тестируется без БД. `CreateBookUseCase::execute(CreateBookCommand $command)` принимает `readonly` DTO и тестируется за миллисекунды.

## Use Case

Один Use Case = одна операция. Один метод `execute`. Принимает Command DTO, возвращает результат.

```php
// src/application/books/usecases/CreateBookUseCase.php
/**
 * @implements UseCaseInterface<CreateBookCommand, int>
 */
final readonly class CreateBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private BookIsbnCheckerInterface $bookIsbnChecker,
        private AuthorExistenceCheckerInterface $authorExistenceChecker,
        private ClockInterface $clock,
    ) {}

    /**
     * @param CreateBookCommand $command
     */
    public function execute(object $command): int
    {
        $authorIds = $command->authorIds->toArray();

        if ($this->bookIsbnChecker->existsByIsbn($command->isbn)) {
            throw new AlreadyExistsException(DomainErrorCode::BookIsbnExists);
        }

        if ($authorIds !== [] && !$this->authorExistenceChecker->existsAllByIds($authorIds)) {
            throw new EntityNotFoundException(DomainErrorCode::BookAuthorsNotFound);
        }

        $currentYear = (int) $this->clock->now()->format('Y');
        $coverImage = $command->storedCover !== null
            ? new StoredFileReference($command->storedCover)
            : null;

        $book = Book::create(
            title: $command->title,
            year: new BookYear($command->year, $currentYear),
            isbn: new Isbn($command->isbn),
            description: $command->description,
            coverImage: $coverImage,
        );
        $book->replaceAuthors($authorIds);

        return $this->bookRepository->save($book);
    }
}
```

Use Case не знает:
- откуда пришёл запрос (HTTP? CLI? очередь?)
- как устроена БД (MySQL? PostgreSQL?)
- как отправляются уведомления

Он знает: интерфейс репозитория, интерфейс checker-а, доменные сущности.

### Другой пример — смена статуса

```php
// src/application/books/usecases/ChangeBookStatusUseCase.php
final readonly class ChangeBookStatusUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private BookPublicationPolicy $publicationPolicy,
    ) {}

    public function execute(object $command): bool
    {
        $book = $this->bookRepository->get($command->bookId);
        $policy = $command->targetStatus === BookStatus::Published
            ? $this->publicationPolicy
            : null;
        $book->transitionTo($command->targetStatus, $policy);

        $this->bookRepository->save($book);

        return true;
    }
}
```

Три строки бизнес-логики: достать, поменять статус, сохранить. Транзакция, трейсинг, маппинг ошибок — в Pipeline (ниже).

## Command DTO

Контракт между Presentation и Application. `readonly`, строго типизированный, без логики:

```php
// src/application/books/commands/CreateBookCommand.php
final readonly class CreateBookCommand
{
    public function __construct(
        public string $title,
        public int $year,
        public ?string $description,
        public string $isbn,
        public AuthorIdCollection $authorIds,
        public ?string $storedCover,
    ) {}
}
```

Никаких `UploadedFile`, `Yii::$app`, HTTP-заголовков. Данные уже преобразованы из формы. Command не знает, откуда пришли данные.

## Query DTO

Read-операции возвращают `readonly` DTO:

```php
// src/application/books/queries/BookReadDto.php
final readonly class BookReadDto
{
    public function __construct(
        public int $id,
        public string $title,
        public int $year,
        public string $isbn,
        public ?string $description,
        public ?string $coverUrl,
        public string $status,
        public int $version,
        /** @var list<int> */
        public array $authorIds = [],
        /** @var list<string> */
        public array $authorNames = [],
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
```

DTO не содержит бизнес-логики. Это проекция данных для чтения, изолированная от ActiveRecord.

## Ports — интерфейсы к внешнему миру

> **Если ты привык к Yii2:** ты пишешь `Yii::$app->cache->get('key')` и получаешь Redis-значение. Удобно, но Use Case теперь зависит от Redis. В тесте нужен Redis. При смене на Memcached — правка бизнес-кода. Port — это `CacheInterface` с методом `get(string $key)`. Application зависит от интерфейса. Infrastructure подставляет Redis, тест подставляет массив. Бизнес-код не меняется.

40+ интерфейсов в `src/application/ports/`. Каждый описывает одну возможность, нужную Application layer:

```
ports/
├── AuthAdapterInterface.php          # Авторизация
├── AuthorExistenceCheckerInterface.php  # Проверка существования авторов
├── BookFinderInterface.php           # Поиск книги по ID
├── BookIsbnCheckerInterface.php      # Проверка уникальности ISBN
├── BookSearcherInterface.php         # Поисковый запрос
├── CacheInterface.php                # Кэш
├── EventPublisherInterface.php       # Публикация событий
├── QueueInterface.php                # Очередь
├── TransactionInterface.php          # Транзакции
├── TranslatorInterface.php           # Переводы
├── ...
```

Пример — разделение интерфейсов для книг (ISP):

```php
// Запись (используется Use Case)
interface BookRepositoryInterface
{
    public function save(Book $book): int;
    public function get(int $id): Book;
    public function delete(Book $book): void;
}

// Чтение по ID (используется Handler, Presentation)
interface BookFinderInterface
{
    public function findById(int $id): ?BookReadDto;
    public function findByIdWithAuthors(int $id): ?BookReadDto;
}

// Поиск (используется Presentation)
interface BookSearcherInterface
{
    public function search(string $term, int $page, int $limit): PagedResultInterface;
    public function searchPublished(string $term, int $page, int $limit): PagedResultInterface;
}
```

Один крупный интерфейс разбит на три мелких. Use Case зависит только от нужных методов.

## Pipeline и Middleware

> **Если ты привык к Yii2:** каждый метод сервиса начинается с `$transaction = Yii::$app->db->beginTransaction(); try { ... } catch { $transaction->rollBack(); }`. Копипаста в каждом методе. Pipeline — аналог `behaviors()` контроллера, но для бизнес-операций: транзакция, трейсинг и маппинг ошибок оборачивают вызов Use Case автоматически. Написал один раз — работает для всех операций.

Сквозные аспекты (транзакции, маппинг ошибок) вынесены в middleware-цепочку:

```php
// src/application/common/pipeline/PipelineFactory.php
public function createDefault(): PipelineInterface
{
    return (new Pipeline())
        ->pipe($this->exceptionTranslationMiddleware)
        ->pipe(new TransactionMiddleware($this->transaction));
}
```

Порядок выполнения:
1. **DomainExceptionTranslationMiddleware** — ловит `DomainException`, превращает в `ApplicationException`
2. **TransactionMiddleware** — оборачивает в транзакцию

Use Case не содержит `try/catch`, не открывает транзакции. Pipeline делает это за него.

## Exception Translation

Domain бросает `DomainException` с `DomainErrorCode`. Application маппит это в типизированные исключения с HTTP-семантикой:

```php
// src/application/common/exceptions/DomainErrorMappingRegistry.php
public function translate(DomainExceptionBase $exception): ApplicationException
{
    $mapping = $this->getMapping($exception->errorCode);

    return match ($mapping->type) {
        ErrorType::NotFound => new EntityNotFoundException(/*...*/),
        ErrorType::AlreadyExists => new AlreadyExistsException(/*...*/),
        ErrorType::BusinessRule => new BusinessRuleException(/*...*/),
        ErrorType::OperationFailed => new OperationFailedException(/*...*/),
    };
}
```

Домен не знает про HTTP-коды. Application знает, что `NotFound` = 404, `AlreadyExists` = 409. Presentation получает типизированное исключение и формирует ответ.

## Config через порты

Конфигурационные значения не читаются из `Yii::$app->params` напрямую. Application определяет структуру через `readonly` DTO:

```php
// src/application/common/config/IdempotencyConfig.php
final readonly class IdempotencyConfig
{
    public function __construct(
        public int $ttl,
        public int $lockTimeout,
        public int $waitSeconds,
        public string $smsPhoneHashKey,
    ) {}
}
```

`ConfigFactory` в Infrastructure создаёт эти объекты из `params.php`. Application получает типизированную конфигурацию, не зная откуда она взялась.

## Итого

Application layer содержит Use Cases (по одному на операцию), Command/Query DTO (контракты между слоями), 40+ портов (интерфейсы к внешнему миру) и Pipeline с middleware (сквозные аспекты). Чистый PHP, тестируется с моками портов.

---

[Далее: Infrastructure layer →](05-infrastructure.md)
