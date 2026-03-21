# Async: события и очереди

[← Назад к оглавлению](../learning.md)

---

В классическом Yii2 SMS отправляются прямо в контроллере. 100 подписчиков — 30 секунд ожидания. В Clean Architecture уведомления уходят в очередь, а пользователь получает ответ мгновенно.

## Путь события

```
Entity (recordEvent)
  → Repository (pullRecordedEvents → publishAfterCommit)
    → COMMIT
    → EventPublisher (dispatch)
      → EventToJobMapper (event → job)
        → Queue (push job)
          → Worker (handle job)
            → Handler (бизнес-логика)
```

## Шаг 1: сущность записывает факт

Книга переходит в статус Published — сущность фиксирует событие:

```php
// src/domain/entities/Book.php
public function transitionTo(BookStatus $target, ?BookPublicationPolicy $policy = null): void
{
    // ... валидация ...
    $oldStatus = $this->status;
    $this->status = $target;

    if ($this->id !== null) {
        $this->recordEvent(new BookStatusChangedEvent(
            $this->id, $oldStatus, $target, $this->year->value,
        ));
    }
}
```

Сущность не знает, что с событием будет дальше. Она записывает факт: "статус изменился".

## Шаг 2: репозиторий публикует после коммита

```php
// src/infrastructure/repositories/BookRepository.php
private function publishRecordedEvents(BookEntity $book): void
{
    foreach ($book->pullRecordedEvents() as $event) {
        $this->eventPublisher->publishAfterCommit($event);
    }
}
```

`publishAfterCommit` — события отправляются после успешного `COMMIT`. Если транзакция откатится, события не уйдут.

## Шаг 3: маппинг события → job

```php
// config/container/adapters.php
EventJobMappingRegistry::class => static fn(Container $c): EventJobMappingRegistry =>
    new EventJobMappingRegistry(
        [
            BookStatusChangedEvent::class => static fn(BookStatusChangedEvent $e): ?NotifySubscribersJob =>
                $e->newStatus === BookStatus::Published
                    ? new NotifySubscribersJob($e->bookId)
                    : null,
        ],
        $c->get(EventSerializer::class),
    ),
```

Маппинг определяет: какое событие → какой job. `BookStatusChangedEvent` с `newStatus = Published` → `NotifySubscribersJob`. Другие переходы статуса → `null` (ничего не делаем).

## Шаг 4: fan-out в очереди

```php
// src/infrastructure/queue/handlers/NotifySubscribersHandler.php
public function handle(int $bookId, Queue $queue): void
{
    $book = $this->bookQueryService->findById($bookId);

    if (!$book instanceof BookReadDto) {
        $this->logger->warning('Book not found for notification', ['book_id' => $bookId]);
        return;
    }

    $message = $this->translator->translate('app', 'notification.book.released', [
        'title' => $book->title,
    ]);

    foreach ($this->queryService->getSubscriberPhonesForBook($bookId) as $phone) {
        $queue->push(new NotifySingleSubscriberJob($phone, $message, $bookId));
    }
}
```

Один `NotifySubscribersJob` порождает N `NotifySingleSubscriberJob` — по одному на каждого подписчика. Каждый отправляется отдельно и может быть повторён независимо при ошибке.

## Шаг 5: отправка SMS

```php
// src/infrastructure/queue/handlers/NotifySingleSubscriberHandler.php
public function handle(string $phone, string $message, int $bookId, Queue $queue): void
{
    $this->smsSender->send($phone, $message);
}
```

`SmsSenderInterface` — порт. В production реализация (`SmsPilotSender`) отправляет через API. В dev — `LogSmsSender`, пишет в лог.

## HandlerAwareQueue

Yii2 Queue сериализует Job целиком. Job не может содержать зависимости (сервисы, репозитории). Решение — Job как DTO, логика в Handler:

```php
// src/infrastructure/queue/NotifySubscribersJob.php
final class NotifySubscribersJob extends BaseObject implements JobInterface
{
    public function __construct(public readonly int $bookId) {}

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
// src/infrastructure/queue/HandlerAwareQueue.php
final class HandlerAwareQueue extends DbQueue
{
    private ?JobHandlerRegistry $handlerRegistry = null;

    public function getHandlerRegistry(): JobHandlerRegistry
    {
        if ($this->handlerRegistry === null) {
            $this->handlerRegistry = Yii::$container->get(JobHandlerRegistry::class);
        }
        return $this->handlerRegistry;
    }
}
```

Job знает только свои данные (`bookId`). Handler получает зависимости через DI-контейнер.

## Dual Write — известный компромисс

Текущая реализация публикует события после `COMMIT`. Если процесс упадёт между коммитом и отправкой в очередь — события потеряются.

```
BEGIN TRANSACTION
  INSERT INTO book ...
COMMIT                      ← Успех
publishAfterCommit(event)   ← Процесс упал здесь — событие потеряно
```

Это осознанный компромисс (документирован в `docs/DECISIONS.md`). Для уведомительных SMS допустимо. Для критичных операций (платежи, заказы) нужен Transactional Outbox — задача `DDD_OUTBOX` в бэклоге.

## Типы событий

```php
// Маркерный интерфейс для асинхронных событий
interface QueueableEvent extends DomainEvent {}

// Синхронное событие (обрабатывается сразу)
interface DomainEvent
{
    public function getEventType(): string;
}
```

`BookStatusChangedEvent` — `QueueableEvent` (уходит в очередь). `BookUpdatedEvent` — обычный `DomainEvent` (обрабатывается синхронно, например инвалидация кэша отчётов).

## Итого

Цепочка: Entity записывает событие → Repository публикует после коммита → EventMapper превращает в Job → Queue ставит в очередь → Worker вызывает Handler → Handler отправляет SMS. Каждое звено независимо и заменяемо. Пользователь не ждёт отправки уведомлений.

---

[Далее: Обеспечение качества →](10-quality.md)
