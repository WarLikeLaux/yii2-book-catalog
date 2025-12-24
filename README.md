# Современный каталог книг: Clean(ish) Architecture на примере Yii 2, асинхронные очереди и гибридный поиск 📚 ⚡️

Проект представляет собой реализацию каталога книг на базе **Yii2 Basic** и **PHP 8.4** с **clean-ish** архитектурой.

Основной акцент сделан на **отделении бизнес-логики от фреймворка**, строгой типизации и отказоустойчивости асинхронных процессов. Продемонстрирован компромиссный подход: Yii остается на уровне представления, а use cases и порты живут отдельно.

## 🛠 Технический стек

*   **PHP:** 8.4 (Strict Types, Constructor Promotion, Readonly Classes).
*   **Framework:** Yii2 Basic.
*   **Database:** MySQL 8.0 (InnoDB + FullText Search).
*   **Async:** `yii2-queue` (DB driver) с реализацией Fan-out паттерна.
*   **Search:** Hybrid SQL Search (FullText + Exact Match) + PJAX.
*   **Testing:** Codeception (Integration + Functional).
*   **Infra:** Docker Compose + Makefile.

## 🏗 Архитектурные решения

### 1. Application Layer (Use Cases, CQS, Ports)
Реализован **CQS (Command Query Separation)** и зависимости через порты:
*   **Write Side (Команды):** Операции изменения состояния инкапсулированы в **Use Cases** (`CreateBookUseCase`, `SubscribeUseCase`). Входные данные строго типизированы через **Command DTO** (`CreateBookCommand`).
*   **Read Side (Запросы):** Чтение данных отделено от бизнес-логики. **QueryServices** возвращают DTO (`BookReadDto`) и `PagedResult` с чистым `PaginationDto` вместо ActiveRecord моделей и framework-объектов.
*   **Ports:** Интерфейсы репозиториев и внешних сервисов находятся в `application/ports` (namespace: `app\application\ports`). Use Cases зависят только от портов, не от конкретных реализаций фреймворка.
*   **Event Publisher:** Use Cases публикуют доменные события через `EventPublisherInterface`, а не создают job напрямую. Это изолирует application layer от инфраструктуры.
*   **UseCaseExecutor:** Cross-cutting concern для выполнения use cases с обработкой ошибок, логированием и уведомлениями. Находится в `application/common` (namespace: `app\application\common`) как общий компонент application layer. Использует порт `NotificationInterface` из `application/ports` для уведомлений, сохраняя независимость от конкретных реализаций (Flash messages, логи). Использует `Yii::t()` для переводов как компромисс для Yii2.
*   **Контроллеры:** Тонкие координаторы, которые только обрабатывают HTTP-запросы и ответы. Вся логика представления (загрузка форм, валидация, маппинг, выполнение use cases, форматирование ответов, извлечение параметров запроса) вынесена в Presentation Services.

### 2. Domain vs ActiveRecord (Clean-ish компромисс)
Доменный слой намеренно минимален: бизнес-операции выполняются через use cases и порты, а ActiveRecord остается источником данных и правил валидации на уровне инфраструктуры. Это осознанный компромисс для Yii2, чтобы не тащить тяжелый маппинг.

**Domain Events:** Доменные события (`BookCreatedEvent`) используются для декoupling между use cases и инфраструктурой. Все доменные события реализуют интерфейс `DomainEvent` с методами `getEventType()` и `getPayload()`. Use Cases публикуют события через типобезопасный метод `publishEvent(DomainEvent $event)` порта `EventPublisherInterface`, а инфраструктурный адаптер (`YiiEventPublisherAdapter`) преобразует их в конкретные job для очереди. Это исключает опечатки в строковых константах и обеспечивает типобезопасность.

### 3. Presentation Layer (Yii2)
Слой представления полностью отделен от бизнес-логики и инкапсулирует всю работу с формами и HTTP-запросами:
*   **Controllers:** Тонкие координаторы, которые только обрабатывают HTTP-запросы и ответы. Не содержат бизнес-логику, маппинг, валидацию, загрузку форм или извлечение параметров запроса. Все контроллеры (`BookController`, `AuthorController`, `SiteController`) следуют единому паттерну: делегируют всю логику представления в Presentation Services.
*   **Forms (`models/forms`, namespace: `app\models\forms`):** Валидация входных данных через `FormModel`.
*   **Mappers (`presentation/mappers`, namespace: `app\presentation\mappers`):** Перевод форм в команды/criteria и обратно (DTO ↔ Form).
*   **Presentation Services (`presentation/services`, namespace: `app\presentation\services`):** Инкапсулируют всю логику представления:
    *   **Form Preparation Services:**
        *   `BookFormPreparationService` — полная обработка форм книг: загрузка из запроса, валидация (включая AJAX), маппинг в команды, выполнение use cases (включая удаление через `processDeleteRequest()`), подготовка данных для представления, извлечение параметров запроса (например, пагинация).
        *   `AuthorFormPreparationService` — аналогично для авторов: обработка форм, извлечение параметров запроса (пагинация в `prepareIndexViewData()`), маппинг, выполнение use cases (включая удаление через `processDeleteRequest()`).
        *   `LoginPresentationService` — обработка формы логина: загрузка из запроса, валидация, выполнение аутентификации через Yii2 User компонент (логика `login()` вынесена из формы в сервис), подготовка данных для представления.
    *   **Search Services:**
        *   `BookSearchPresentationService` — обработка поиска книг: извлечение параметров, маппинг criteria, вызов query service, создание data provider.
        *   `AuthorSearchPresentationService` — обработка поиска авторов (AJAX): извлечение параметров, валидация, маппинг, форматирование JSON-ответа.
    *   **Report Services:**
        *   `ReportPresentationService` — генерация отчетов: валидация фильтров, маппинг criteria, выполнение запросов через UseCaseExecutor.
    *   **Subscription Services:**
        *   `SubscriptionPresentationService` — обработка подписок: загрузка формы, валидация, маппинг, выполнение use case, форматирование JSON-ответа.
*   **DTO Results (`presentation/dto`, namespace: `app\presentation\dto`):** Типизированные результаты обработки форм (`BookCreateFormResult`, `BookUpdateFormResult`, `AuthorCreateFormResult`, `AuthorUpdateFormResult`) для передачи данных между Presentation Services и контроллерами. Все DTO содержат `viewData` для единообразной передачи данных в представления.
*   **Adapters (`presentation/adapters`, namespace: `app\presentation\adapters`):** `PagedResult` преобразуется в `DataProvider` через `PagedResultDataProviderFactory` без логики в контроллерах. Адаптер `PagedResultDataProvider` преобразует чистый `PaginationDto` обратно в `yii\data\Pagination` для Yii2 виджетов.

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
        
        // Публикация типобезопасного доменного события через порт
        $event = new BookCreatedEvent($bookId, $command->title);
        $this->eventPublisher->publishEvent($event);
        
        return $bookId;
    }
}

// Presentation Service - адаптация HTTP к Use Case
class BookFormPreparationService {
    public function processCreateRequest(Request $request, Response $response): BookCreateFormResult {
        $form->loadFromRequest($request);  // HTTP детали
        $form->validate();                  // Валидация форм
        $command = $mapper->toCommand($form); // Маппинг
        $success = $useCaseExecutor->execute(...); // Вызов Use Case
        return new BookCreateFormResult(...);  // Данные для представления
    }
}
```

### 5. DTO & Forms для валидации
Слой представления отделен от домена.
*   **Forms (`models/forms`, namespace: `app\models\forms`):** Валидируют сырые пользовательские данные (HTTP request).
*   **Command DTO (`application/**/commands`, namespace: `app\application\**\commands`):** Передают уже валидные данные в ядро приложения.
*   **Result DTO (`presentation/dto`, namespace: `app\presentation\dto`):** Типизированные результаты обработки форм для передачи между Presentation Services и контроллерами.
*   **PaginationDto (`application/common/dto`, namespace: `app\application\common\dto`):** Чистый DTO для пагинации без зависимостей от фреймворка. Репозитории создают его вручную из параметров, сохраняя использование `ActiveDataProvider` для выполнения запросов (eager loading).
*   Это позволяет безопасно обрабатывать загрузку файлов и сложную логику без засорения доменных сущностей правилами валидации форм.

### 6. Infrastructure Layer
*   **ActiveRecord и DB:** Реализации портов живут в `infrastructure` (namespace: `app\infrastructure`).
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
    1.  `CreateBookUseCase` публикует типобезопасное доменное событие `BookCreatedEvent` через метод `publishEvent()` порта `EventPublisherInterface`.
    2.  `YiiEventPublisherAdapter` преобразует доменное событие в `NotifySubscribersJob` (Dispatcher).
    3.  `NotifySubscribersJob` находит целевую аудиторию и нарезает задачи батчами.
    4.  Создаются тысячи легких `NotifySingleSubscriberJob` для каждого получателя.
*   **Результат:** Изоляция ошибок (сбой одного SMS не ломает рассылку), возможность параллельной обработки несколькими воркерами, полная независимость use cases от конкретных реализаций очереди, и типобезопасность через интерфейс `DomainEvent`.

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
Внешние зависимости закрыты портами в `application/ports` и интерфейсами внешних сервисов в `interfaces/`:

**Порты application layer (`application/ports`):**
*   `EventPublisherInterface`: Абстракция для публикации доменных событий. Предоставляет типобезопасный метод `publishEvent(DomainEvent $event)` для публикации доменных событий, реализующих интерфейс `DomainEvent`. Use Cases не знают о конкретных реализациях очереди.
*   `NotificationInterface`: Абстракция для уведомлений пользователя (Flash messages, логи). Используется в `UseCaseExecutor` для показа сообщений об успехе/ошибках. Реализации находятся в `services/notifications/` (FlashNotificationService, LogNotificationService).
*   `TranslatorInterface`: Абстракция для переводов сообщений. Используется в `UseCaseExecutor` для переводов сообщений об ошибках. Реализация `YiiTranslatorAdapter` находится в `infrastructure/adapters/`.
*   `PagedResultInterface`: Возвращает чистый `PaginationDto` вместо framework-объектов, сохраняя независимость application layer от Yii2.

**Интерфейсы внешних сервисов (`interfaces/`):**
*   `SmsSenderInterface`: Позволяет прозрачно менять провайдеров (Smspilot / Mock).
*   `FileStorageInterface`: Абстракция для сохранения файлов (Local / S3).

**Направление зависимостей:** Application layer зависит только от портов в `application/ports`. Infrastructure и Presentation реализуют эти порты, сохраняя правильное направление зависимостей Clean Architecture.

### 12. Структура проекта

```
./
├── application/              # Application Layer (Use Cases, Queries, Ports)
│   ├── books/
│   │   ├── commands/        # Command DTOs (CreateBookCommand, UpdateBookCommand)
│   │   ├── queries/         # Query Services и Read DTOs
│   │   └── usecases/        # Use Cases (CreateBookUseCase, UpdateBookUseCase)
│   ├── authors/
│   ├── common/
│   │   ├── dto/            # Общие DTO (PaginationDto)
│   │   └── UseCaseExecutor.php  # Cross-cutting concern для выполнения use cases с обработкой ошибок
│   └── ports/               # Порты application layer (EventPublisherInterface, NotificationInterface, PagedResultInterface)
├── domain/                  # Domain Layer (Entities, Value Objects, Domain Exceptions)
│   ├── events/             # Domain Events (BookCreatedEvent, DomainEvent interface)
│   └── exceptions/         # Domain Exceptions (DomainException)
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

**Примечание:** Порты для application layer находятся в `application/ports` (например, `NotificationInterface`, `EventPublisherInterface`). Интерфейсы внешних сервисов (SMS, File Storage) остаются в `interfaces/` как внешние зависимости.
```

**Примечание:** В коде используется namespace `app\`, что соответствует стандартному Yii2 алиасу `@app`. Структура директорий в корне проекта соответствует namespace-ам (например, `application/` → `app\application\*`).

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
    public function processUpdateRequest(int $id, Request $request, Response $response): BookUpdateFormResult
    {
        $viewData = $this->prepareUpdateViewData($id);
        $form = $viewData['model'];

        if (!$form->loadFromRequest($request)) {
            return new BookUpdateFormResult($form, $viewData, false);
        }

        if ($request->isAjax) {
            $response->format = Response::FORMAT_JSON;
            $ajaxValidation = ActiveForm::validate($form);
            return new BookUpdateFormResult($form, $viewData, false, null, $ajaxValidation);
        }

        if (!$form->validate()) {
            return new BookUpdateFormResult($form, $viewData, false);
        }

        $command = $this->bookFormMapper->toUpdateCommand($id, $form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->updateBookUseCase->execute($command),
            Yii::t('app', 'Book has been updated'),
            ['book_id' => $id]
        );

        if ($success) {
            return new BookUpdateFormResult($form, $viewData, true, ['view', 'id' => $id]);
        }

        return new BookUpdateFormResult($form, $viewData, false);
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
// Логика login() вынесена из формы в сервис для соответствия Clean Architecture
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

// LoginPresentationService - логика аутентификации вынесена из формы
class LoginPresentationService
{
    public function processLoginRequest(Request $request, Response $response): array
    {
        $viewData = $this->prepareLoginViewData();
        $form = $viewData['model'];

        if (!$form->load($request->post())) {
            return ['success' => false, 'viewData' => $viewData];
        }

        if (!$form->validate()) {
            $form->password = '';
            return ['success' => false, 'viewData' => ['model' => $form]];
        }

        // Логика login() теперь в сервисе, а не в форме
        $user = $form->getUser();
        if (!$user || !$user->validatePassword($form->password)) {
            $form->addError('password', Yii::t('app', 'Incorrect username or password.'));
            $form->password = '';
            return ['success' => false, 'viewData' => ['model' => $form]];
        }

        $duration = $form->rememberMe ? 3600 * 24 * 30 : 0;
        Yii::$app->user->login($user, $duration);

        return ['success' => true, 'viewData' => $viewData];
    }
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
    public function publishEvent(DomainEvent $event): void
    {
        // Используем типобезопасный метод для публикации доменных событий
        // Проверяем тип события через instanceof для типобезопасности
        if (!($event instanceof BookCreatedEvent)) {
            return;
        }

        $this->queue->push(new NotifySubscribersJob([
            'bookId' => $event->bookId,
            'title' => $event->title,
        ]));
    }
}

// Пример: контроллер делегирует удаление в Presentation Service
public function actionDelete(int $id): Response
{
    $this->bookFormPreparationService->processDeleteRequest($id);
    return $this->redirect(['index']);
    // Контроллер не знает о создании команд и выполнении use cases
}
```

### 13. Компромиссы Clean-ish архитектуры

Проект следует принципам **Clean Architecture**, но с осознанными компромиссами для Yii2, что делает его **Clean-ish** (не строго Clean, но близко к идеалу). Все компромиссы приняты намеренно для баланса между чистотой архитектуры и практичностью работы с Yii2.

#### 13.1. Domain Layer минимален

**Компромисс:** Доменный слой намеренно минимален — бизнес-операции выполняются через Use Cases и порты, а ActiveRecord остается источником данных и правил валидации на уровне инфраструктуры.

**Почему:** В строгой Clean Architecture доменные сущности были бы чистыми PHP классами без зависимостей от фреймворка. Для Yii2 это означало бы тяжелый маппинг между доменными объектами и ActiveRecord моделями, что усложнило бы код без существенной пользы.

**Что получили:** 
* Use Cases остаются независимыми от фреймворка
* ActiveRecord используется только в Infrastructure layer
* Доменные события (`BookCreatedEvent`) обеспечивают декoupling
* Бизнес-логика изолирована в Use Cases

#### 13.2. Репозитории используют ActiveRecord для запросов

**Компромисс:** Репозитории используют `ActiveDataProvider` и ActiveRecord для выполнения запросов (сохранение eager loading через `with()`), но возвращают чистые DTO вместо моделей.

**Почему:** Yii2 ActiveRecord предоставляет мощные возможности оптимизации запросов (eager loading, оптимизация N+1 проблем), которые сложно реализовать вручную без потери производительности.

**Что получили:**
* Application layer получает чистые DTO без зависимостей от фреймворка
* Сохраняются все преимущества Yii2 ActiveRecord (eager loading, оптимизация запросов)
* Репозитории создают чистый `PaginationDto` вместо передачи framework-объектов
* В presentation layer адаптер преобразует `PaginationDto` обратно в `yii\data\Pagination` для виджетов

#### 13.3. Presentation Layer использует Yii2 компоненты

**Компромисс:** Presentation layer использует Yii2 компоненты (`ActiveForm`, `DataProvider`, `Response`), но полностью изолирован от бизнес-логики.

**Почему:** Yii2 предоставляет мощные компоненты для работы с формами, пагинацией и HTTP, которые сложно заменить без потери функциональности и удобства разработки.

**Что получили:**
* Вся логика представления инкапсулирована в Presentation Services
* Контроллеры остаются тонкими координаторами
* Use Cases не знают о формах, HTTP, валидации форм
* Бизнес-логика полностью независима от способа представления

#### 13.4. Итоговый баланс

Все компромиссы приняты осознанно и документированы. Результат:
* ✅ Application layer полностью независим от фреймворка
* ✅ Use Cases содержат чистую бизнес-логику
* ✅ Инфраструктура изолирована через порты и адаптеры
* ✅ Сохранены все преимущества Yii2 (ActiveRecord, виджеты, формы)
* ✅ Код остается читаемым и поддерживаемым

Это **Clean-ish** архитектура: не строго Clean, но максимально близко к идеалу с учетом практических ограничений Yii2.

## 🚀 Установка и запуск

Проект полностью контейнеризирован. Все управление осуществляется через `Makefile`.

### Инициализация
Развернуть контейнеры, установить зависимости, применить миграции и наполнить базу тестовыми данными:

```bash
make init
```

Приложение будет доступно по адресу: [http://localhost:8000](http://localhost:8000)

### Тестирование
Запуск всех тестов (интеграционные + функциональные):

```bash
make test
```

**Интеграционные тесты** (7 тестов, 13 assertions) — проверяют бизнес-логику use cases:
- Создание книг с авторами (`CreateBookUseCase`)
- Публикацию доменных событий (`BookCreatedEvent`)
- Транзакции и rollback при ошибках
- Подписки на авторов (`SubscribeUseCase`)
- Бизнес-правила (уникальность ISBN, предотвращение дубликатов)

**Функциональные тесты** (20 тестов, 49 assertions) — проверяют работу через HTTP:
- Просмотр страниц (книги, авторы, отчет)
- Создание и редактирование через формы
- AJAX-запросы (подписки, поиск авторов)
- Авторизация и навигация

**Итого: 27 тестов, 62 assertions**

Дополнительные команды:
```bash
make test-integration  # Только интеграционные тесты (use cases)
make test-functional   # Только функциональные тесты (HTTP)
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

