# Domain layer — бизнес без фреймворка

[← Назад к оглавлению](../learning.md)

---

Domain — внутренний слой архитектуры. Чистый PHP, ноль зависимостей от Yii2, БД или HTTP. Здесь живут бизнес-правила: что такое книга, какой ISBN валиден, когда можно публиковать.

> **Если ты привык к Yii2:** Domain — это как ActiveRecord, из которого убрали `save()`, `find()`, `rules()`, `tableName()` и все связи с БД. Осталась только бизнес-логика. Зачем? Потому что `Book::find()->where(...)` в контроллере — это не бизнес-правило, а деталь реализации. А "нельзя опубликовать книгу без обложки" — бизнес-правило, которое должно работать независимо от того, откуда вызвано: из Web-формы, API, консольной команды или теста.

## Entity: Rich Model

В Yii2 ActiveRecord — это и модель данных, и точка доступа к БД, и объект валидации. В Clean Architecture эти ответственности разделены. Entity — только бизнес-логика.

Сущность — не контейнер данных с геттерами/сеттерами. Это объект с поведением. Методы сущности реализуют бизнес-правила и контролируют инварианты.

```php
// src/domain/entities/Book.php
final class Book implements RecordableEntityInterface
{
    use RecordsEvents;

    private function __construct(
        public private(set) ?int $id,
        string $title,
        public private(set) BookYear $year,
        public private(set) Isbn $isbn,
        // ...
        public private(set) BookStatus $status,
        public private(set) int $version,
    ) {
        $this->title = $title;
    }
}
```

Конструктор приватный. Создание — через фабричные методы:

```php
// Новая книга (ID = null, статус = Draft, версия = 1)
$book = Book::create(
    title: 'Чистая архитектура',
    year: new BookYear(2017),
    isbn: new Isbn('9780134494166'),
    description: 'Описание книги...',
    coverImage: null,
);

// Восстановление из БД (все поля заполнены)
$book = Book::reconstitute(
    id: 42,
    title: 'Чистая архитектура',
    year: new BookYear(2017),
    // ...
    status: BookStatus::Published,
    version: 3,
);
```

`create` — для нового объекта, `reconstitute` — для гидрации из БД. Разделение позволяет применять разные правила: при создании статус всегда `Draft`, при восстановлении — тот, что в БД.

### Бизнес-правила в методах

ISBN можно менять только у черновика:

```php
public function correctIsbn(Isbn $isbn): void
{
    if ($this->status !== BookStatus::Draft) {
        throw new BusinessRuleException(DomainErrorCode::BookIsbnChangePublished);
    }

    $this->isbn = $isbn;
}
```

Смена статуса проверяет допустимость перехода и политику публикации:

```php
public function transitionTo(BookStatus $target, ?BookPublicationPolicy $policy = null): void
{
    if (!$this->status->canTransitionTo($target)) {
        throw new BusinessRuleException(DomainErrorCode::BookInvalidStatusTransition);
    }

    if ($this->status === BookStatus::Draft && $target === BookStatus::Published) {
        if (!$policy instanceof BookPublicationPolicy) {
            throw new BusinessRuleException(DomainErrorCode::BookPublishWithoutPolicy);
        }
        $policy->ensureCanPublish($this);
    }

    $oldStatus = $this->status;
    $this->status = $target;

    if ($this->id !== null) {
        $this->recordEvent(new BookStatusChangedEvent(
            $this->id, $oldStatus, $target, $this->year->value,
        ));
    }
}
```

Логика перехода — в сущности, а не в контроллере или сервисе. Невозможно перевести книгу в невалидный статус.

## Value Objects

> **Если ты привык к Yii2:** в `rules()` ты пишешь `[['isbn'], 'string', 'max' => 20]`. Это проверяет формат при сохранении формы. Но что мешает где-нибудь в коде написать `$book->isbn = 'не-isbn'` напрямую? Ничего. Value Object решает эту проблему: если ты получил объект `Isbn` — он гарантированно валиден. Не нужно проверять повторно. Невалидный ISBN не может существовать как объект — конструктор выбросит исключение.

Value Object — иммутабельный объект, определяемый значением. Два ISBN с одинаковым номером — один и тот же объект. Value Object валидирует себя в конструкторе: невалидный экземпляр не может существовать.

### Isbn

```php
// src/domain/values/Isbn.php
final readonly class Isbn implements \Stringable
{
    private const array ISBN13_PREFIXES = ['978', '979'];

    public private(set) string $value;

    public function __construct(string $value)
    {
        $normalized = self::normalizeIsbn($value);

        if (!self::isValid($normalized)) {
            throw new ValidationException(DomainErrorCode::IsbnInvalidFormat);
        }

        $this->value = $normalized;
    }
}
```

Нормализация (удаление дефисов и пробелов) и валидация (контрольная сумма ISBN-10/ISBN-13) — внутри объекта. Код, получивший `Isbn`, гарантированно работает с валидным значением.

### BookYear

```php
// src/domain/values/BookYear.php
final readonly class BookYear implements \Stringable
{
    public function __construct(
        public int $value,
        ?int $currentYear = null,
    ) {
        if ($value < 1000) {
            throw new ValidationException(DomainErrorCode::BookYearTooOld);
        }

        if ($currentYear !== null && $value > $currentYear + 1) {
            throw new ValidationException(DomainErrorCode::BookYearFuture);
        }
    }
}
```

### BookStatus (enum с правилами переходов)

```php
// src/domain/values/BookStatus.php
enum BookStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => $target === self::Published,
            self::Published => $target === self::Draft || $target === self::Archived,
            self::Archived => $target === self::Draft,
        };
    }
}
```

Допустимые переходы описаны в enum, а не разбросаны по контроллерам.

### Phone

```php
// src/domain/values/Phone.php
final readonly class Phone implements \Stringable
{
    private const string PATTERN = '/^\+[1-9]\d{6,14}$/';

    public private(set) string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if ($normalized === '' || !preg_match(self::PATTERN, $normalized)) {
            throw new ValidationException(DomainErrorCode::PhoneInvalidFormat);
        }
        $this->value = $normalized;
    }
}
```

E.164 формат. Невалидный номер — исключение при создании.

## Domain Events

> **Если ты привык к Yii2:** в `afterSave()` ты пишешь `Yii::$app->queue->push(new NotifyJob(...))`. Entity знает про очередь. Domain Events — другой подход: Entity говорит "статус изменился" (`recordEvent`), а кто и как на это реагирует — решают другие слои. Сущность не знает ни про SMS, ни про очередь, ни про email. Это позволяет тестировать Entity без инфраструктуры и добавлять новые реакции на событие без изменения доменного кода.

Сущность записывает факты, которые произошли. Не отправляет SMS, не ставит в очередь — фиксирует.

```php
// src/domain/events/BookStatusChangedEvent.php
final readonly class BookStatusChangedEvent implements QueueableEvent
{
    public const string EVENT_TYPE = 'book.status_changed';

    public function __construct(
        public int $bookId,
        public BookStatus $oldStatus,
        public BookStatus $newStatus,
        public int $year,
    ) {}
}
```

Трейт `RecordsEvents` накапливает события:

```php
trait RecordsEvents
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /** @return list<DomainEvent> */
    public function pullRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];
        return $events;
    }
}
```

Репозиторий вызывает `pullRecordedEvents()` после сохранения и публикует события. Сущность не знает, что с ними происходит.

## Domain Service

`BookPublicationPolicy` — единственный domain service в проекте. Проверяет кросс-полевые правила публикации:

```php
// src/domain/services/BookPublicationPolicy.php
final readonly class BookPublicationPolicy
{
    public function ensureCanPublish(Book $book): void
    {
        if ($book->authorIds === []) {
            throw new BusinessRuleException(DomainErrorCode::BookPublishWithoutAuthors);
        }

        if (!$book->coverImage instanceof StoredFileReference) {
            throw new BusinessRuleException(DomainErrorCode::BookPublishWithoutCover);
        }

        if (!$this->hasValidDescription($book->description)) {
            throw new ValidationException(DomainErrorCode::BookPublishShortDescription);
        }
    }
}
```

Логика, которая не принадлежит одной сущности, но остаётся бизнес-правилом.

## Specification Pattern

> **Если ты привык к Yii2:** `Book::find()->where(['year' => 2024])->andWhere(['like', 'title', $term])` — это SQL-логика прямо в контроллере или сервисе. Что если нужен полнотекстовый поиск? `MATCH AGAINST` для MySQL, `tsvector` для PostgreSQL, `LIKE` для SQLite. Три разных SQL для одного бизнес-требования "найти книгу по тексту". Specification выносит критерий поиска в доменный объект (`new FullTextSpecification($term)`), а конкретный SQL генерируется в Infrastructure через Visitor. Домен говорит "что искать", инфраструктура решает "как искать".

Критерии поиска — доменные объекты. SQL — дело инфраструктуры.

```php
// src/domain/specifications/BookSpecificationInterface.php
interface BookSpecificationInterface
{
    public function accept(BookSpecificationVisitorInterface $visitor): void;
}
```

Конкретные спецификации:

```php
final readonly class FullTextSpecification implements BookSpecificationInterface
{
    public function __construct(private string $query) {}

    public function getQuery(): string { return $this->query; }

    public function accept(BookSpecificationVisitorInterface $visitor): void
    {
        $visitor->visitFullText($this);
    }
}
```

Композитные спецификации позволяют комбинировать критерии:

```php
new CompositeAndSpecification([
    new StatusSpecification(BookStatus::Published),
    new CompositeOrSpecification([
        new FullTextSpecification($term),
        new AuthorSpecification($term),
        new IsbnPrefixSpecification($term),
    ]),
]);
```

Visitor в инфраструктуре превращает это в SQL (подробнее — в главе про Infrastructure).

## Исключения

Иерархия доменных исключений с типизированными кодами ошибок:

```php
abstract class DomainException extends \RuntimeException
{
    public function __construct(
        public readonly DomainErrorCode $errorCode,
        // ...
    ) {}
}
```

```php
// Конкретные типы
final class ValidationException extends DomainException {}      // 422
final class BusinessRuleException extends DomainException {}    // 422
final class EntityNotFoundException extends DomainException {}  // 404
final class AlreadyExistsException extends DomainException {}   // 409
```

`DomainErrorCode` — enum с 45 кейсами. Каждый кейс имеет атрибут `#[ErrorMapping]`, указывающий тип ошибки и поле формы:

```php
enum DomainErrorCode: string
{
    #[ErrorMapping(ErrorType::BusinessRule, field: 'isbn')]
    case BookIsbnChangePublished = 'book.isbn.change_published';

    #[ErrorMapping(ErrorType::NotFound)]
    case BookNotFound = 'book.not_found';

    // ...
}
```

Домен выбрасывает типизированное исключение. Application и Presentation слои маппят его на HTTP-ответ. Домен не знает про HTTP-коды.

## Итого

Domain layer содержит 3 сущности, 8 Value Objects, 7 спецификаций, 1 domain service, 3 интерфейса репозиториев и иерархию исключений. Ноль импортов из Yii2, ноль зависимостей от БД. Тестируется за миллисекунды.

---

[Далее: Application layer →](04-application.md)
