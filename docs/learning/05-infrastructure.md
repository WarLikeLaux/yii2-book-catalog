# Infrastructure layer — адаптеры к реальному миру

[← Назад к оглавлению](../learning.md)

---

Infrastructure реализует интерфейсы, определённые в Domain и Application. Здесь живут ActiveRecord, SQL, Redis, SMS — всё, что Domain не должен знать.

## От ActiveRecord к Repository: зачем этот путь

> **Если ты привык к Yii2**, `Book::find()->where(['id' => $id])->one()` и `$model->save()` — естественный способ работы с данными. ActiveRecord даёт всё: ORM, dirty tracking, relations, behaviors. Зачем от этого отказываться?
>
> Ответ: **не отказываемся.** ActiveRecord по-прежнему используется — но только внутри Infrastructure. Снаружи (из Use Case) виден только интерфейс `BookRepositoryInterface` с методами `save()`, `get()`, `delete()`.
>
> **Что это даёт:**
>
> 1. **Тестируемость.** Use Case тестируется с фейковым репозиторием в памяти. Без БД, без Yii, за миллисекунды. В классическом Yii2 тест `BookService::create()` требует поднять базу и сидеть в `setUp()` 2 секунды.
>
> 2. **Скрытие деталей.** Use Case вызывает `$this->bookRepository->save($book)`. Ему не нужно знать: что внутри транзакция, что авторы синхронизируются через `book_authors`, что версия проверяется через optimistic lock, что события публикуются после коммита. Всё это — детали реализации.
>
> 3. **Замена БД.** Проект работает и с MySQL, и с PostgreSQL. Use Case об этом не знает — разница скрыта в реализации репозитория и Query Service.
>
> 4. **Единая точка изменений.** Нужно добавить кэширование при чтении? Обернуть репозиторий декоратором. Нужен трейсинг? Ещё один декоратор. Бизнес-код не меняется.

## Repository Pattern

Domain определяет интерфейс:

```php
interface BookRepositoryInterface
{
    public function save(Book $book): int;
    public function get(int $id): Book;
    public function getByIdAndVersion(int $id, int $expectedVersion): Book;
    public function delete(Book $book): void;
}
```

Infrastructure реализует через ActiveRecord:

```php
// src/infrastructure/repositories/BookRepository.php
public function save(BookEntity $book): int
{
    return $this->db->transaction(function () use ($book): int {
        $isNew = $book->getId() === null;
        $model = $isNew
            ? new Book()
            : $this->getArForEntity($book, Book::class, DomainErrorCode::BookNotFound);
        $model->version = $book->version;

        $this->hydrator->hydrate($model, $book, [
            'title',
            'year',
            'isbn',
            'description',
            'cover_url' => static fn(BookEntity $e): ?string => $e->coverImage?->getPath(),
            'status' => static fn(BookEntity $e): string => $e->status->value,
        ]);

        $this->persist($model, DomainErrorCode::BookStaleData, DomainErrorCode::BookIsbnExists);

        if ($isNew) {
            $this->assignIdentity($book, (int)$model->id);
        }

        $this->syncBookAuthors((int)$model->id, $book->authorIds);
        $this->publishRecordedEvents($book);

        return (int)$model->id;
    });
}
```

Use Case вызывает `$this->bookRepository->save($book)` и не знает, что внутри — ActiveRecord, транзакция, гидрация, синхронизация авторов.

## ActiveRecordHydrator

Перекладывает данные из доменной сущности в ActiveRecord-модель:

```php
$this->hydrator->hydrate($model, $book, [
    'title',                  // простое поле — копируется как есть
    'year',                   // Value Object → автоматический unboxing
    'isbn',                   // Value Object → автоматический unboxing
    'cover_url' => static fn(BookEntity $e): ?string => $e->coverImage?->getPath(),  // кастомный маппинг
    'status' => static fn(BookEntity $e): string => $e->status->value,               // enum → string
]);
```

Hydrator автоматически "разворачивает" Value Objects (вызывает `__toString()` или берёт `->value`). Для нестандартного маппинга — callback.

Обратное направление (AR → Entity) — через `reconstitute`:

```php
// src/infrastructure/repositories/BookRepository.php
private function toEntity(Book $model): BookEntity
{
    return BookEntity::reconstitute(
        id: (int)$model->id,
        title: $model->title,
        year: new BookYear((int)$model->year),
        isbn: new Isbn($model->isbn),
        description: $model->description,
        coverImage: $model->cover_url !== null ? new StoredFileReference($model->cover_url) : null,
        authorIds: $model->getAuthorIds(),
        status: BookStatus::from($model->status),
        version: (int)$model->version,
    );
}
```

## BaseActiveRecordRepository

Базовый класс с Identity Map на WeakReference:

```php
// src/infrastructure/repositories/BaseActiveRecordRepository.php
abstract class BaseActiveRecordRepository
{
    /** @var WeakMap<object, ActiveRecord> */
    private WeakMap $identityMap;
}
```

Identity Map отслеживает, какой AR-модели соответствует какая сущность. При повторном `save` не создаёт новую запись, а обновляет существующую. `WeakReference` — объект автоматически удаляется из карты при уничтожении сущности.

Методы базового класса:

- `persist()` — сохраняет AR с обработкой `StaleObjectException` и `IntegrityException`
- `assignIdentity()` — присваивает ID новой сущности через Reflection
- `publishRecordedEvents()` — публикует события после коммита

## Query Services

Read-операции отделены от write. `BookQueryService` реализует `BookFinderInterface` и `BookSearcherInterface`:

```php
// src/infrastructure/queries/BookQueryService.php
public function search(string $term, int $page, int $limit): PagedResultInterface
{
    $query = Book::find();

    if ($term !== '') {
        $specification = $this->specificationFactory->createFromSearchTerm($term);
        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->db);
        $specification->accept($visitor);
    }

    return $this->createPagedResult($query, $page, $limit, BookReadDto::class);
}
```

Query Service возвращает `BookReadDto` — не доменную сущность. Для чтения не нужен полноценный агрегат.

## Specification Visitor → SQL

Visitor превращает доменные спецификации в SQL:

```php
// src/infrastructure/queries/ActiveQueryBookSpecificationVisitor.php
public function visitFullText(FullTextSpecification $spec): void
{
    $term = $spec->getQuery();

    if ($this->supportsFullText()) {
        $this->query->andWhere(
            'MATCH(title, description) AGAINST(:term IN BOOLEAN MODE)',
            [':term' => $term . '*'],
        );
    } else {
        $this->query->andWhere(['or',
            ['like', 'title', $term],
            ['like', 'description', $term],
        ]);
    }
}

public function visitYear(YearSpecification $spec): void
{
    $this->query->andWhere(['year' => $spec->getYear()]);
}

public function visitAuthor(AuthorSpecification $spec): void
{
    $this->query->innerJoinWith('authors')
        ->andWhere(['like', 'author.fio', $spec->getAuthorName()]);
}
```

Один Visitor поддерживает MySQL (MATCH AGAINST), PostgreSQL (tsvector) и SQLite (LIKE fallback). Домен не знает о различиях БД.

## Адаптеры Yii2

Каждый инфраструктурный компонент Yii2 обёрнут в адаптер, реализующий порт:

```php
// src/infrastructure/adapters/YiiTransactionAdapter.php
final class YiiTransactionAdapter implements TransactionInterface
{
    public function __construct(private AppDbConnection $db) {}

    public function execute(callable $callback): mixed
    {
        return $this->db->transaction($callback);
    }
}
```

```php
// src/infrastructure/adapters/YiiCacheAdapter.php
final class YiiCacheAdapter implements CacheInterface
{
    public function __construct(private Cache $cache) {}

    public function get(string $key): mixed
    {
        $value = $this->cache->get($key);
        return $value === false ? null : $value;
    }
}
```

Use Case работает с `TransactionInterface`, не зная что внутри — `Yii::$app->db->beginTransaction()`.

## Декораторы

Кэширование добавляется через декораторы — без изменения основного кода:

```php
// src/infrastructure/queries/decorators/ReportQueryServiceCachingDecorator.php
final class ReportQueryServiceCachingDecorator implements ReportQueryServiceInterface
{
    public function getTopAuthors(ReportCriteria $criteria): array
    {
        $key = 'report_top_authors_' . $criteria->year;

        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->inner->getTopAuthors($criteria);
        $this->cache->set($key, $result, $this->cacheTtl);

        return $result;
    }
}
```

DI-контейнер собирает цепочку: `CachingDecorator → ReportQueryService`.

## ActiveRecord модели

AR-модели живут в `src/infrastructure/persistence/` и используются только для persistence:

```php
// src/infrastructure/persistence/Book.php
final class Book extends ActiveRecord
{
    public static function tableName(): string { return '{{%book}}'; }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            [
                'class' => OptimisticLockBehavior::class,
                'value' => fn(): int => $this->version ?? 1,
            ],
        ];
    }

    public function optimisticLock(): string { return 'version'; }
}
```

AR не используется в Domain или Application. Это инструмент инфраструктуры — так же, как SQL-запрос или Redis-клиент.

## AppDbConnection

Обёртка над `Yii::$app->db` для корректного DI:

```php
// src/infrastructure/components/AppDbConnection.php
final class AppDbConnection extends Connection {}
```

Стандартный DI Yii2 не может инжектить `Yii::$app->db` напрямую (вызывает рекурсию). `AppDbConnection` — пустой наследник, зарегистрированный как компонент приложения. Это позволяет писать `__construct(AppDbConnection $db)` вместо `Yii::$app->db`.

## Итого

Infrastructure содержит реализации репозиториев (через AR), Query Services (read-only), адаптеры (Cache, Queue, Transaction), декораторы (Tracing, Caching), ActiveRecord модели (только persistence) и обёртки Yii2-компонентов (для DI). Весь Yii2-специфичный код заперт в этом слое.

---

[Далее: Presentation layer →](06-presentation.md)
