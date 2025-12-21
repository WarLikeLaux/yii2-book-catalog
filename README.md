# Yii2 Library System (Clean Architecture Demo)

Проект представляет собой реализацию каталога книг на базе **Yii2 Basic** и **PHP 8.4** с **clean-ish** архитектурой.

Основной акцент сделан на **отделении бизнес-логики от фреймворка**, строгой типизации и отказоустойчивости асинхронных процессов. Продемонстрирован компромиссный подход: Yii остается на уровне представления, а use cases и порты живут отдельно.

## 🛠 Технический стек

*   **PHP:** 8.4 (Strict Types, Constructor Promotion, Readonly Classes).
*   **Framework:** Yii2 Basic.
*   **Database:** MySQL 8.0 (InnoDB + FullText Search).
*   **Async:** `yii2-queue` (DB driver) с реализацией Fan-out паттерна.
*   **Search:** Hybrid SQL Search (FullText + Exact Match) + PJAX.
*   **Testing:** Codeception (Unit).
*   **Infra:** Docker Compose + Makefile.

## 🏗 Архитектурные решения

### 1. Application Layer (Use Cases, CQS, Ports)
Реализован **CQS (Command Query Separation)** и зависимости через порты:
*   **Write Side (Команды):** Операции изменения состояния инкапсулированы в **Use Cases** (`CreateBookUseCase`, `SubscribeUseCase`). Входные данные строго типизированы через **Command DTO** (`CreateBookCommand`).
*   **Read Side (Запросы):** Чтение данных отделено от бизнес-логики. **QueryServices** возвращают DTO (`BookReadDto`) и `PagedResult` с чистым `PaginationDto` вместо ActiveRecord моделей и framework-объектов.
*   **Ports:** Интерфейсы репозиториев и внешних сервисов находятся в `app/application/ports`. Use Cases зависят только от портов, не от конкретных реализаций фреймворка.
*   **Event Publisher:** Use Cases публикуют доменные события через `EventPublisherInterface`, а не создают job напрямую. Это изолирует application layer от инфраструктуры.
*   **Контроллеры:** Тонкие координаторы, которые только обрабатывают HTTP-запросы и ответы. Вся логика представления (загрузка форм, валидация, маппинг, выполнение use cases, форматирование ответов, извлечение параметров запроса) вынесена в Presentation Services.

### 2. Domain vs ActiveRecord (Clean-ish компромисс)
Доменный слой намеренно минимален: бизнес-операции выполняются через use cases и порты, а ActiveRecord остается источником данных и правил валидации на уровне инфраструктуры. Это осознанный компромисс для Yii2, чтобы не тащить тяжелый маппинг.

**Domain Events:** Доменные события (`BookCreatedEvent`) используются для декoupling между use cases и инфраструктурой. Use Cases публикуют события через порт, а инфраструктурный адаптер (`YiiEventPublisherAdapter`) преобразует их в конкретные job для очереди.

### 3. Presentation Layer (Yii2)
Слой представления полностью отделен от бизнес-логики и инкапсулирует всю работу с формами и HTTP-запросами:
*   **Controllers:** Тонкие координаторы, которые только обрабатывают HTTP-запросы и ответы. Не содержат бизнес-логику, маппинг, валидацию, загрузку форм или извлечение параметров запроса. Все контроллеры (`BookController`, `AuthorController`, `SiteController`) следуют единому паттерну: делегируют всю логику представления в Presentation Services.
*   **Forms (`app/models/forms`):** Валидация входных данных через `FormModel`.
*   **Mappers (`app/presentation/mappers`):** Перевод форм в команды/criteria и обратно (DTO ↔ Form).
*   **Presentation Services (`app/presentation/services`):** Инкапсулируют всю логику представления:
    *   **Form Preparation Services:**
        *   `BookFormPreparationService` — полная обработка форм книг: загрузка из запроса, валидация (включая AJAX), маппинг в команды, выполнение use cases, подготовка данных для представления, извлечение параметров запроса (например, пагинация).
        *   `AuthorFormPreparationService` — аналогично для авторов: обработка форм, извлечение параметров запроса (пагинация в `prepareIndexViewData()`), маппинг, выполнение use cases.
        *   `LoginPresentationService` — обработка формы логина: загрузка из запроса, валидация, выполнение аутентификации через Yii2 User компонент, подготовка данных для представления.
    *   **Search Services:**
        *   `BookSearchPresentationService` — обработка поиска книг: извлечение параметров, маппинг criteria, вызов query service, создание data provider.
        *   `AuthorSearchPresentationService` — обработка поиска авторов (AJAX): извлечение параметров, валидация, маппинг, форматирование JSON-ответа.
    *   **Report Services:**
        *   `ReportPresentationService` — генерация отчетов: валидация фильтров, маппинг criteria, выполнение запросов через UseCaseExecutor.
    *   **Subscription Services:**
        *   `SubscriptionPresentationService` — обработка подписок: загрузка формы, валидация, маппинг, выполнение use case, форматирование JSON-ответа.
*   **DTO Results (`app/presentation/dto`):** Типизированные результаты обработки форм (`CreateFormResult`, `UpdateFormResult`, `AuthorCreateFormResult`, `AuthorUpdateFormResult`) для передачи данных между Presentation Services и контроллерами. Все DTO содержат `viewData` для единообразной передачи данных в представления.
*   **Adapters (`app/presentation/adapters`):** `PagedResult` преобразуется в `DataProvider` без логики в контроллерах.

### 4. Разделение ответственности: Use Cases vs Presentation Services

**Use Cases (Application Layer)** — бизнес-логика:
*   Работают с готовыми Command/DTO объектами (уже валидные данные)
*   Не знают о формах, HTTP, валидации форм, форматах ответов
*   Независимы от способа представления (HTTP, CLI, API, тесты)
*   Содержат чистую бизнес-логику: транзакции, бизнес-правила, координация репозиториев

**Presentation Services (Presentation Layer)** — логика представления:
*   Загружают данные из HTTP-запросов (`Request`)
*   Извлекают и валидируют параметры запроса (GET/POST параметры, пагинация)
*   Валидируют формы (`Form->validate()`)
*   Обрабатывают AJAX-валидацию (`ActiveForm::validate()`)
*   Маппят формы ↔ команды (`FormMapper`)
*   Устанавливают форматы ответов (`Response->format`)
*   Вызывают Use Cases и обрабатывают результаты
*   Подготавливают данные для представлений (`viewData`, `prepareIndexViewData()`, `prepareCreateViewData()`)

**Пример разделения:**

```php
// Use Case - только бизнес-логика, не знает о HTTP и конкретных реализациях
class CreateBookUseCase {
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
        private readonly TransactionInterface $transaction,
        private readonly EventPublisherInterface $eventPublisher, // Порт, не конкретная реализация
    ) {}
    
    public function execute(CreateBookCommand $command): int {
        // Транзакции, создание книги, синхронизация авторов
        $bookId = $this->bookRepository->create(...);
        
        // Публикация события через порт, не создание job напрямую
        $this->eventPublisher->publish('book.created', [
            'bookId' => $bookId,
            'title' => $command->title,
        ]);
        
        return $bookId;
    }
}

// Presentation Service - адаптация HTTP к Use Case
class BookFormPreparationService {
    public function processCreateRequest(Request $request, Response $response): CreateFormResult {
        $form->loadFromRequest($request);  // HTTP детали
        $form->validate();                  // Валидация форм
        $command = $mapper->toCommand($form); // Маппинг
        $success = $useCaseExecutor->execute(...); // Вызов Use Case
        return new CreateFormResult(...);  // Данные для представления
    }
}
```

### 5. DTO & Forms для валидации
Слой представления отделен от домена.
*   **Forms (`app/models/forms`):** Валидируют сырые пользовательские данные (HTTP request).
*   **Command DTO (`app/application/**/commands`):** Передают уже валидные данные в ядро приложения.
*   **Result DTO (`app/presentation/dto`):** Типизированные результаты обработки форм для передачи между Presentation Services и контроллерами.
*   **PaginationDto (`app/application/common/dto`):** Чистый DTO для пагинации без зависимостей от фреймворка. Репозитории создают его вручную из параметров, сохраняя использование `ActiveDataProvider` для выполнения запросов (eager loading).
*   Это позволяет безопасно обрабатывать загрузку файлов и сложную логику без засорения доменных сущностей правилами валидации форм.

### 6. Infrastructure Layer
*   **ActiveRecord и DB:** Реализации портов живут в `app/infrastructure`.
*   **Queue/File Storage:** Подключаются через интерфейсы и DI.
*   **Event Publisher Adapter:** `YiiEventPublisherAdapter` преобразует доменные события в конкретные job для очереди. Это позволяет use cases оставаться независимыми от фреймворка.
*   **Пагинация:** Репозитории используют `ActiveDataProvider` для выполнения запросов (сохранение eager loading через `with()`), но создают чистый `PaginationDto` вместо передачи framework-объекта в application layer.

### 7. Code Quality & Standards
*   **Strict Types:** Весь проект работает в режиме `declare(strict_types=1)`.
*   **Static Analysis:** Внедрен Advanced Coding Standard (на базе **Slevomat**).
*   **Linter:** Код автоматически форматируется и проверяется командой `make lint-fix`.

### 8. Масштабируемая очередь (Fan-out Pattern)
Реализована система уведомлений подписчиков о выходе книг.
*   **Проблема:** Отправка SMS тысячам подписчиков в одном Job-е может привести к тайм-аутам и блокировке воркера.
*   **Решение:** Используется паттерн **Fan-out**.
    1.  `CreateBookUseCase` публикует событие `book.created` через `EventPublisherInterface`.
    2.  `YiiEventPublisherAdapter` преобразует событие в `NotifySubscribersJob` (Dispatcher).
    3.  `NotifySubscribersJob` находит целевую аудиторию и нарезает задачи батчами.
    4.  Создаются тысячи легких `NotifySingleSubscriberJob` для каждого получателя.
*   **Результат:** Изоляция ошибок (сбой одного SMS не ломает рассылку), возможность параллельной обработки несколькими воркерами, и полная независимость use cases от конкретных реализаций очереди.

### 9. Чистая пагинация (Clean Pagination DTO)
Реализована пагинация без зависимостей от framework-объектов в application layer.
*   **Проблема:** `yii\data\Pagination` протекал через `PagedResultInterface`, создавая зависимость application layer от фреймворка.
*   **Решение:** 
    1. Репозитории используют `ActiveDataProvider` для выполнения запросов (сохранение eager loading через `with()`).
    2. Создают чистый `PaginationDto` вручную из параметров `page`, `pageSize` и `totalCount`.
    3. `PagedResultInterface` возвращает `PaginationDto` вместо `?object`.
    4. В presentation layer адаптер (`PagedResultDataProvider`) преобразует `PaginationDto` обратно в `yii\data\Pagination` для Yii2 виджетов.
*   **Результат:** Application layer независим от фреймворка, но сохраняет все преимущества Yii2 ActiveRecord (eager loading, оптимизация запросов).

### 10. Гибридный поиск (Universal Search)
Реализован "умный" поиск по каталогу без использования внешних движков (Elasticsearch), но с оптимизацией под MySQL.
*   **FullText Index:** Используется для поиска по `title` и `description` (O(1)).
*   **Exact Match:** Для ISBN и Года используются точные совпадения.
*   **UX:** Обернуто в **PJAX** для фильтрации без перезагрузки страницы.

### 11. Dependency Injection
Внешние зависимости закрыты интерфейсами и портами (`app/interfaces`, `app/application/ports`):
*   `SmsSenderInterface`: Позволяет прозрачно менять провайдеров (Smspilot / Mock).
*   `FileStorageInterface`: Абстракция для сохранения файлов (Local / S3).
*   `EventPublisherInterface`: Абстракция для публикации доменных событий. Use Cases не знают о конкретных реализациях очереди.
*   `PagedResultInterface`: Возвращает чистый `PaginationDto` вместо framework-объектов, сохраняя независимость application layer от Yii2.

### 12. Структура проекта

```
app/
├── application/              # Application Layer (Use Cases, Queries, Ports)
│   ├── books/
│   │   ├── commands/        # Command DTOs (CreateBookCommand, UpdateBookCommand)
│   │   ├── queries/         # Query Services и Read DTOs
│   │   └── usecases/        # Use Cases (CreateBookUseCase, UpdateBookUseCase)
│   ├── authors/
│   ├── common/
│   │   └── dto/            # Общие DTO (PaginationDto)
│   └── ports/               # Интерфейсы репозиториев и сервисов (EventPublisherInterface)
├── domain/                  # Domain Layer (Entities, Value Objects, Domain Exceptions)
│   └── events/             # Domain Events (BookCreatedEvent)
├── infrastructure/          # Infrastructure Layer (ActiveRecord, DB, Queue)
│   ├── adapters/           # Адаптеры портов (YiiEventPublisherAdapter, YiiQueueAdapter)
│   └── repositories/        # Реализации репозиториев через ActiveRecord
├── presentation/            # Presentation Layer (Controllers, Forms, Mappers, Services)
│   ├── services/            # Presentation Services (инкапсулируют логику представления)
│   ├── mappers/            # Маппинг между DTO и Forms
│   ├── dto/                # DTO для результатов обработки форм
│   └── adapters/           # Адаптеры для Yii2 компонентов (PagedResultDataProvider)
├── controllers/             # Тонкие HTTP-контроллеры
├── models/                  # ActiveRecord модели и Forms
└── interfaces/              # Интерфейсы внешних сервисов (SMS, File Storage)
```

**Пример использования Presentation Service:**

```php
// Контроллер (тонкий, только координация HTTP-запросов/ответов)
public function actionUpdate(int $id): string|Response|array
{
    if (!$this->request->isPost) {
        $viewData = $this->bookFormPreparationService->prepareUpdateViewData($id);
        return $this->render('update', $viewData);
    }

    $result = $this->bookFormPreparationService->processUpdateRequest($id, $this->request, $this->response);

    if ($result->ajaxValidation !== null) {
        return $result->ajaxValidation;
    }

    if ($result->success && $result->redirectRoute !== null) {
        return $this->redirect($result->redirectRoute);
    }

    return $this->render('update', $result->viewData);
}

// Presentation Service (инкапсулирует всю логику представления)
class BookFormPreparationService
{
    public function processUpdateRequest(int $id, Request $request, Response $response): UpdateFormResult
    {
        $viewData = $this->prepareUpdateViewData($id);
        $form = $viewData['model'];

        if (!$form->loadFromRequest($request)) {
            return new UpdateFormResult($form, $viewData, false);
        }

        if ($request->isAjax) {
            $response->format = Response::FORMAT_JSON;
            $ajaxValidation = ActiveForm::validate($form);
            return new UpdateFormResult($form, $viewData, false, null, $ajaxValidation);
        }

        if (!$form->validate()) {
            return new UpdateFormResult($form, $viewData, false);
        }

        $command = $this->bookFormMapper->toUpdateCommand($id, $form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->updateBookUseCase->execute($command),
            Yii::t('app', 'Book has been updated'),
            ['book_id' => $id]
        );

        if ($success) {
            return new UpdateFormResult($form, $viewData, true, ['view', 'id' => $id]);
        }

        return new UpdateFormResult($form, $viewData, false);
    }
}

// Пример: контроллер не знает о формате ответа и параметрах запроса
public function actionSearch(): array
{
    return $this->authorSearchPresentationService->search($this->request, $this->response);
    // Presentation Service сам устанавливает формат JSON и извлекает параметры
}

// Пример: извлечение параметров пагинации вынесено в Presentation Service
public function actionIndex(): string
{
    $viewData = $this->authorFormPreparationService->prepareIndexViewData($this->request);
    return $this->render('index', $viewData);
    // Контроллер не знает о том, как извлекается параметр 'page' и валидируется
}

// Пример: унифицированный подход для всех контроллеров - использование viewData
public function actionUpdate(int $id): string|Response
{
    if (!$this->request->isPost) {
        $viewData = $this->authorFormPreparationService->prepareUpdateViewData($id);
        return $this->render('update', $viewData);
    }

    $result = $this->authorFormPreparationService->processUpdateRequest($id, $this->request);

    if ($result->success && $result->redirectRoute !== null) {
        return $this->redirect($result->redirectRoute);
    }

    return $this->render('update', $result->viewData);
    // Всегда используем viewData из результата, а не прямой доступ к form
}

// Пример: LoginPresentationService - даже стандартная Yii2 форма логина следует паттерну
public function actionLogin(): Response|string
{
    if (!Yii::$app->user->isGuest) {
        return $this->goHome();
    }

    if (!$this->request->isPost) {
        $viewData = $this->loginPresentationService->prepareLoginViewData();
        return $this->render('login', $viewData);
    }

    $result = $this->loginPresentationService->processLoginRequest($this->request, $this->response);

    if ($result['success']) {
        return $this->goBack();
    }

    return $this->render('login', $result['viewData']);
}

// Presentation Service извлекает и валидирует параметры
class AuthorFormPreparationService
{
    public function prepareIndexViewData(Request $request): array
    {
        $page = max(1, (int)$request->get('page', 1)); // Извлечение и валидация
        $pageSize = max(1, (int)$request->get('pageSize', 20)); // Извлечение и валидация
        $queryResult = $this->authorQueryService->getIndexProvider($page, $pageSize);
        $dataProvider = $this->dataProviderFactory->create($queryResult);
        return ['dataProvider' => $dataProvider];
    }
    
    public function prepareViewViewData(int $id): array
    {
        $author = $this->authorQueryService->getById($id);
        return ['author' => $author];
    }
}

// Контроллер делегирует всю логику представления
class AuthorController
{
    public function actionIndex(): string
    {
        $viewData = $this->authorFormPreparationService->prepareIndexViewData($this->request);
        return $this->render('index', $viewData);
    }
    
    public function actionView(int $id): string
    {
        $viewData = $this->authorFormPreparationService->prepareViewViewData($id);
        return $this->render('view', $viewData);
    }
}

// Пример: Event Publisher изолирует Use Case от инфраструктуры
// Infrastructure Adapter преобразует доменное событие в конкретный job
class YiiEventPublisherAdapter implements EventPublisherInterface
{
    public function publish(string $eventType, array $payload): void
    {
        if ($eventType !== 'book.created') {
            return;
        }
        
        // Создание конкретного job происходит только в infrastructure layer
        $this->queue->push(new NotifySubscribersJob([
            'bookId' => $payload['bookId'],
            'title' => $payload['title'],
        ]));
    }
}
```

## 🚀 Установка и запуск

Проект полностью контейнеризирован. Все управление осуществляется через `Makefile`.

### Инициализация
Развернуть контейнеры, установить зависимости, применить миграции и наполнить базу тестовыми данными:

```bash
make init
```

Приложение будет доступно по адресу: [http://localhost:8000](http://localhost:8000)

### Тестирование
Запуск Unit-тестов (покрывают сервисный слой, валидацию и бизнес-логику):

```bash
make test
```

### Основные команды

| Команда | Описание |
|---|---|
| `make up` / `make down` | Управление контейнерами |
| `make seed` | Генерация демо-данных (Книги, Авторы) |
| `make lint-fix` | Авто-фикс стиля кода (PHPCS) |
| `make queue-info` | Статус очереди задач |
| `make sms-logs` | Просмотр логов отправки SMS (Mock) |
| `make shell` | Консоль PHP контейнера |

## ⚙️ Конфигурация

Настройки окружения находятся в файле `.env`.
*   `SMS_API_KEY`: Установите `MOCK_KEY` для эмуляции отправки (запись в лог) или реальный ключ.

