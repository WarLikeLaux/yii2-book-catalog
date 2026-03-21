# Dependency Injection без магии

[← Назад к оглавлению](../learning.md)

---

Clean Architecture требует инверсии зависимостей: Use Case зависит от интерфейса, а не от конкретной реализации. В Yii2 нет полноценного DI-контейнера с autowiring, но его хватает.

## Конструкторная инъекция

Все зависимости — через конструктор. Service Locator (`Yii::$app->get()`) запрещён:

```php
// Так делаем
final readonly class CreateBookUseCase implements UseCaseInterface
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private BookIsbnCheckerInterface $bookIsbnChecker,
        private AuthorExistenceCheckerInterface $authorExistenceChecker,
        private ClockInterface $clock,
    ) {}
}
```

```php
// Так НЕ делаем
$repo = Yii::$app->get('bookRepository');
```

## Сборка графа зависимостей

Конфигурация DI разбита на файлы по ответственности:

```
config/container/
├── common.php            # Clock, AutoMapper, Logger
├── infrastructure.php    # DB, Redis, Mutex, Checkers
├── repositories.php      # Repository интерфейсы → реализации
├── services.php          # Query Services, Storage, Health
└── adapters.php          # Порты → Yii2 адаптеры
```

Пример привязки интерфейса к реализации:

```php
// config/container/repositories.php
BookRepositoryInterface::class => BookRepository::class,
```

Yii2 Container видит, что `CreateBookUseCase` требует `BookRepositoryInterface` в конструкторе, и достаёт его из конфигурации.

## Проблема с Yii2 компонентами

Стандартные компоненты Yii2 (`Yii::$app->db`, `Yii::$app->cache`) — singletons, управляемые Application. DI-контейнер не может инжектить их напрямую: попытка создать `Connection` через контейнер вызывает рекурсию.

Решение — обёртки:

```php
// src/infrastructure/components/AppDbConnection.php
final class AppDbConnection extends Connection {}
```

```php
// config/web.php
'db' => [
    'class' => AppDbConnection::class,
    'dsn' => '...',
],
```

Теперь `AppDbConnection` — и компонент приложения (`Yii::$app->db`), и класс для инъекции:

```php
public function __construct(private AppDbConnection $db) {}
```

Аналогично `AppRedisConnection`, `AppMysqlMutex`, `AppPgsqlMutex`.

## Адаптеры для портов

Порт (интерфейс) определён в Application:

```php
// src/application/ports/TransactionInterface.php
interface TransactionInterface
{
    public function execute(callable $callback): mixed;
}
```

Адаптер реализует порт через Yii2:

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

Привязка в контейнере:

```php
// config/container/adapters.php
TransactionInterface::class => static fn(Container $c): TransactionInterface =>
    new YiiTransactionAdapter($c->get(AppDbConnection::class)),
```

## Декораторы в DI

DI-контейнер привязывает интерфейсы к реализациям:

```php
BookRepositoryInterface::class => BookRepository::class,
```

Use Case получает `BookRepositoryInterface` и не знает деталей реализации.

## DI в фоновых задачах

Job-классы — DTO, сериализуемые в очередь. Логика — в отдельных Handler-ах:

```php
// src/infrastructure/queue/NotifySubscribersJob.php
final class NotifySubscribersJob extends BaseObject implements JobInterface
{
    public function __construct(
        public readonly int $bookId,
    ) {}

    public function execute($queue): void
    {
        $registry = $queue instanceof HandlerAwareQueue
            ? $queue->getHandlerRegistry()
            : null;
        $registry?->handle($this, $queue);
    }
}
```

```php
// src/infrastructure/queue/JobHandlerRegistry.php
final class JobHandlerRegistry
{
    /** @var array<class-string, callable> */
    private array $handlers;

    public function handle(JobInterface $job, Queue $queue): void
    {
        $handler = $this->handlers[$job::class] ?? null;
        $handler($job, $queue);
    }
}
```

Handler получает зависимости через DI. Job — чистый DTO без зависимостей. Это обходит ограничение Yii2 Queue, где Job должен быть сериализуемым.

## Итого

DI в проекте строится на трёх принципах: конструкторная инъекция (без Service Locator), интерфейсы для всех внешних зависимостей (40+ портов), обёртки Yii2-компонентов для совместимости с контейнером. Граф зависимостей описан в `config/container/` и проверяется PHPStan-правилом `DisallowYiiTOutsideAdaptersRule`.

---

[Далее: Async: события и очереди →](09-async.md)
