<div align="center">

# 📚 Yii2 Book Catalog

**Modern Clean Architecture • PHP 8.4 • Async Queues • Hybrid Search**

[![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Yii2](https://img.shields.io/badge/Yii2-Framework-blue?style=for-the-badge&logo=yii&logoColor=white)](https://www.yiiframework.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/Tests-252_passed-success?style=for-the-badge&logo=codecov&logoColor=white)](#-тестирование-и-покрытие-кода)
[![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen?style=for-the-badge&logo=codecov&logoColor=white)](#-тестирование-и-покрытие-кода)
[![Mutation Score](https://img.shields.io/badge/MSI-94%25-brightgreen?style=for-the-badge&logo=probot&logoColor=white)](#-тестирование-и-покрытие-кода)

---

<p align="center">
  <b>🏛 Clean-ish Architecture</b> • <b>⚡ CQS Pattern</b> • <b>🎯 Value Objects</b> • <b>📨 Domain Events</b> • <b>🔄 Async Fan-out</b>
</p>

</div>

---

Проект представляет собой реализацию каталога книг на базе **Yii2 Basic** и **PHP 8.4** с **Clean-ish** архитектурой.

Основной акцент сделан на **отделении бизнес-логики от фреймворка**, строгой типизации и отказоустойчивости асинхронных процессов. Продемонстрирован компромиссный подход: Yii остается на уровне представления, а use cases и порты живут отдельно.

📋 Подробная история изменений доступна в [CHANGELOG.md](CHANGELOG.md).

🏗 **Подробное сравнение архитектуры** с типичным Yii2-подходом: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)

## ✨ Ключевые особенности

| 🏛️ Архитектура | ⚡ Производительность |
| :--- | :--- |
| 🔹 **Clean-ish Architecture**<br>Компромисс между чистотой и прагматизмом | 🚀 **Async Fan-out**<br>Масштабируемые уведомления |
| 🔹 **CQS Pattern**<br>Разделение команд и запросов | 🔍 **Hybrid Search**<br>FullText + Exact Match |
| 🔹 **Value Objects**<br>`Isbn`, `BookYear` для бизнес-правил | 🛡 **Idempotency**<br>Защита от дублей в очередях |
| 🔹 **Domain Events**<br>Асинхронное взаимодействие | ⚡ **PJAX**<br>Мгновенная фильтрация |

| 🧪 Качество кода | 🐳 DevOps Ready |
| :--- | :--- |
| ✅ **252 теста** (549 assertions)<br>100% покрытие кода тестами | 🐳 **Docker Compose**<br>Полный стек одной командой |
| ✅ **PHPStan Level 9**<br>Custom Architecture Rules | 🛠 **Makefile**<br>Автоматизация рутины |
| ✅ **Mutation Testing**<br>Infection PHP (MSI > 94%) | 🚀 **Automatic Doc Validation**<br>Custom PHP metrics linter |
| ✅ **Automated Refactoring**<br>Rector & Deptrac | 🔄 **Hot Reload**<br>Быстрая разработка |

## 🛠 Технический стек

| Категория | Технология | Описание |
|-----------|------------|----------|
| **Язык** | [![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net/) | PHPStan Level 9, Strict Types, Constructor Promotion |
| **Framework** | [![Yii2](https://img.shields.io/badge/Yii-2.0-blue?logo=yii)](https://www.yiiframework.com/) | Basic Template с DI Container |
| **Database** | [![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/) | InnoDB + FullText Search |
| **Queue** | `yii2-queue` | DB Driver + Fan-out Pattern |
| **Testing** | [![Codeception](https://img.shields.io/badge/Codeception-5.0-purple)](https://codeception.com/) | Unit + Functional, 100% Coverage |
| **Infra** | [![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](https://www.docker.com/) | PHP 8.4 + MySQL 8 + Queue Worker |
| **Quality** | `Rector`, `PHPStan`, `Deptrac`, `Advisories` | Strict Static Analysis & Security Checks |

## 🏗 Архитектурные решения

### 1. Application Layer (Use Cases, CQS, Ports)
Реализован **CQS (Command Query Separation)** и зависимости через порты:
*   **Write Side (Команды):** операции изменения состояния инкапсулированы в **Use Cases** (`CreateBookUseCase`, `SubscribeUseCase`). Входные данные строго типизированы через **Command DTO** (`CreateBookCommand`).
*   **Read Side (Запросы):** чтение данных отделено от бизнес-логики. **QueryServices** возвращают DTO (`BookReadDto`) и `PagedResult` с чистым `PaginationDto` вместо ActiveRecord моделей и framework-объектов.
*   **Ports:** интерфейсы репозиториев и внешних сервисов находятся в `application/ports` (namespace: `app\application\ports`). Use Cases зависят только от портов, не от конкретных реализаций фреймворка.
*   **Event Publisher:** Use Cases публикуют доменные события через `EventPublisherInterface`, а не создают job напрямую. Это изолирует application layer от инфраструктуры.
*   **UseCaseExecutor:** сквозной функционал (Cross-cutting concern) для выполнения use cases с обработкой ошибок, логированием и уведомлениями. Находится в `application/common`. Использует нативные средства локализации фреймворка (`Yii::t`) для упрощения.
*   **Контроллеры:** выступают оркестраторами. Загружают данные в формы и запускают валидацию, но делегируют выполнение бизнес-операций в Command Services, а выборку данных — в View Services. Не содержат самой бизнес-логики.

### 2. Domain vs ActiveRecord ("Cheap DDD")
Доменный слой защищен **Value Objects** (`Isbn`, `BookYear`) для критических бизнес-правил. Валидация происходит **до** попадания в ActiveRecord/DB. 
*   **Value Objects:** гарантируют консистентность данных (нельзя создать объект с неверным ISBN).
*   **ActiveRecord:** остается для persistence и простых правил (unique, string length), но бизнес-валидация делегируется Value Objects.
Это осознанный компромисс: мы не делаем Full Entities, но используем VO для защиты инвариантов.

**Domain Events:** Доменные события (`BookCreatedEvent`) используются для **decoupling** (развязки) между use cases и инфраструктурой. Все доменные события реализуют интерфейс `DomainEvent` с методами `getEventType()` и `getPayload()`. Use Cases публикуют события через типобезопасный метод `publishEvent(DomainEvent $event)` порта `EventPublisherInterface`. Инфраструктурный адаптер (`YiiEventPublisherAdapter`) преобразует их в конкретные job для очереди. Это исключает опечатки и обеспечивает типобезопасность.

### 3. Presentation Layer (Yii2)
Слой представления полностью отделен от бизнес-логики и инкапсулирует всю работу с формами и HTTP-запросами:
*   **Controllers:** отвечают за валидацию входных данных (через Forms) и управление потоком выполнения. Вызывают Command Services для изменения состояния и View Services для получения данных.
*   **Forms (`presentation/forms`, namespace: `app\presentation\forms`):** валидация входных данных через `FormModel`.
*   **Mappers (`presentation/mappers`, namespace: `app\presentation\mappers`):** перевод форм в команды/criteria и обратно (DTO ↔ Form).
*   **Presentation Services (`presentation/services`, namespace: `app\presentation\services`):** реализуют разделение ответственности (CQRS):
    *   **Command Services:** пишущие операции (Create, Update, Delete). Принимают формы, выполняют Use Cases, возвращают примитивы (ID, bool). Чистая логика, без `Request`/`Response`.
    *   **View Services:** читающие операции. Подготавливают DTO и DataProvider-ы для отображения в шаблонах.
    *   **Search Services:** специфичные сервисы для AJAX-поиска (например, Select2).
*   **Adapters (`presentation/adapters`, namespace: `app\presentation\adapters`):** преобразуют чистые DTO пагинации обратно в Yii2 форматы (`PagedResult` -> `DataProvider`) для совместимости с GridView.

### 4. Разделение ответственности: Use Cases vs Presentation Services

**Use Cases (Application Layer)** — бизнес-логика:
*   Работают с готовыми Command/DTO объектами (уже валидные данные)
*   Не знают о формах, HTTP, валидации форм, форматах ответов
*   Независимы от способа представления (HTTP, CLI, API, тесты)
*   Содержат чистую бизнес-логику: транзакции, бизнес-правила, координация репозиториев

**Presentation Services (Presentation Layer)** — разделены на Command и View:
*   **Command Services:** Принимают заполненные формы, маппят их в команды и выполняют Use Cases. Не зависят от `Request` или `Response`.
*   **View Services:** Подготавливают данные для отображения (списки авторов, книги).
*   **Controller:** Выступает как Orchestrator. Загружает данные из HTTP-запроса, запускает валидацию форм, вызывает сервисы и формирует ответ.

**Пример разделения:**

```php
// Контроллер - только HTTP логика
public function actionCreate(): string|Response
{
    $form = new BookForm();
    
    // HTTP: загрузка и валидация
    if ($this->request->isPost && $form->load($this->request->post()) && $form->validate()) {
        // Command Service: бизнес-операция
        $bookId = $this->commandService->createBook($form);
        if ($bookId) {
            return $this->redirect(['view', 'id' => $bookId]);
        }
    }

    // View Service: данные для формы
    return $this->render('create', [
        'model' => $form,
        'authors' => $this->viewService->getAuthorsList(),
    ]);
}

// Command Service - чистая логика без HTTP зависимостей
class BookCommandService 
{
    public function createBook(BookForm $form): ?int 
    {
        $coverPath = $this->fileStorage->save($form->cover);
        $command = $this->mapper->toCreateCommand($form, $coverPath); 
        
        // Выполняем Use Case через экзекутор (транзакции, логи, уведомления)
        $bookId = null;
        $this->useCaseExecutor->execute(function() use ($command, &$bookId) {
            $bookId = $this->useCase->execute($command);
        });
        
        return $bookId;
    }
}
```

### 5. DTO & Forms для валидации
Слой представления отделен от домена.
*   **Forms (`presentation/forms`, namespace: `app\presentation\forms`):** валидируют сырые пользовательские данные (HTTP request).
*   **Command DTO (`application/**/commands`, namespace: `app\application\**\commands`):** передают валидные данные в Use Case.
*   **PaginationDto (`application/common/dto`, namespace: `app\application\common\dto`):** чистый DTO для пагинации.
*   Это позволяет разлепить валидацию HTTP-запроса и бизнес-правила (которые живут в Value Objects).

### 6. Infrastructure Layer
*   **ActiveRecord и DB:** Реализации портов живут в `infrastructure` (namespace: `app\infrastructure`).
*   **Queue/File Storage:** Подключаются через интерфейсы и DI.
*   **Event Publisher Adapter:** `YiiEventPublisherAdapter` преобразует доменные события в конкретные job для очереди. Это позволяет use cases оставаться независимыми от фреймворка.
*   **Пагинация:** Репозитории используют `ActiveDataProvider` для выполнения запросов (сохранение eager loading через `with()`), но создают чистый `PaginationDto` вместо передачи framework-объекта в application layer.

### 7. Code Quality & Standards
*   **Strict Types:** Весь проект работает в режиме `declare(strict_types=1)`.
*   **Static Analysis:** Внедрен Advanced Coding Standard (на базе **Slevomat**) и **PHPStan** (Level 9).
*   **Refactoring:** Используется **Rector** для автоматизированного обновления кода до PHP 8.4 и соблюдения Code Quality правил.
*   **Linter:** Код автоматически форматируется и проверяется командой `make lint-fix`.

### 8. Масштабируемая очередь (Fan-out Pattern)
Реализована система уведомлений подписчиков о выходе книг.
*   **Проблема:** Отправка SMS тысячам подписчиков в одном Job-е может привести к тайм-аутам и блокировке воркера.
*   **Решение:** Используется паттерн **Fan-out**.
    1.  `CreateBookUseCase` публикует типобезопасное доменное событие `BookCreatedEvent` через метод `publishEvent()` порта `EventPublisherInterface`.
    2.  `YiiEventPublisherAdapter` преобразует доменное событие в `NotifySubscribersJob` (Dispatcher).
    3.  `NotifySubscribersJob` получает список подписчиков.
    4.  Для каждого подписчика создается отдельная задача `NotifySingleSubscriberJob`.
*   **Результат:** Изоляция ошибок (сбой одного SMS не ломает рассылку), возможность параллельной обработки несколькими воркерами, полная независимость use cases от конкретных реализаций очереди, и типобезопасность через интерфейс `DomainEvent`.

### 9. Чистая пагинация (Pagination DTO)
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
Все внешние зависимости закрыты портами в `application/ports`:

**Порты application layer (`application/ports`):**
*   `EventPublisherInterface`: Публикация доменных событий. Реализация в `infrastructure/adapters/`.
*   `NotificationInterface`: Уведомления пользователя (Flash messages, логи). Реализации в `infrastructure/services/notifications/`.
*   `TranslatorInterface`: Переводы сообщений. Реализация в `infrastructure/adapters/`.
*   `SmsSenderInterface`: Отправка SMS. Реализация в `infrastructure/services/sms/`.
*   `FileStorageInterface`: Сохранение файлов. Реализация в `infrastructure/services/storage/`.
*   `PagedResultInterface`: Пагинация без зависимостей от фреймворка.

**Направление зависимостей:** Application layer зависит только от портов. Infrastructure и Presentation реализуют эти порты, сохраняя правильное направление зависимостей Clean Architecture.

### 12. Структура проекта

```
yii2-book-catalog/
├── bin/                      # Кастомные скрипты (валидаторы ченджлога и документации)
├── application/              # Application Layer (Use Cases, Queries, Ports)
│   ├── books/
│   │   ├── commands/        # Command DTOs (CreateBookCommand, UpdateBookCommand)
│   │   ├── queries/         # Query Services и Read DTOs
│   │   └── usecases/        # Use Cases (CreateBookUseCase, UpdateBookUseCase)
│   ├── authors/
│   ├── subscriptions/
│   ├── reports/
│   ├── common/
│   │   ├── dto/            # Общие DTO (PaginationDto, QueryResult)
│   │   └── UseCaseExecutor.php
│   └── ports/               # ВСЕ порты (EventPublisher, Notification, SMS, FileStorage, Translator)
├── domain/                  # Domain Layer
│   ├── events/             # Domain Events (BookCreatedEvent, DomainEvent interface)
│   ├── exceptions/         # Domain Exceptions (DomainException)
│   └── values/             # Value Objects (Isbn, BookYear)
├── infrastructure/          # Infrastructure Layer
│   ├── adapters/           # Адаптеры портов (YiiEventPublisher, YiiTranslator, etc.)
│   ├── persistence/        # ActiveRecord модели (Author, Book, Subscription, User)
│   ├── queue/              # Queue Jobs (NotifySubscribersJob, NotifySingleSubscriberJob)
│   ├── repositories/       # Реализации репозиториев
│   ├── services/           # Реализации сервисов (SMS, FileStorage, Notifications)
│   └── phpstan/            # Кастомные правила статического анализа (архитектурный контроль)
├── presentation/            # Presentation Layer
│   ├── controllers/        # HTTP-контроллеры
│   ├── views/              # Yii2 views
│   ├── forms/              # Form models (BookForm, AuthorForm, LoginForm)
│   ├── validators/         # Yii2 validators (IsbnValidator, UniqueFioValidator)
│   ├── widgets/            # Yii2 widgets (Alert)
│   ├── mail/               # Email шаблоны
│   ├── services/           # Presentation Services (Command & View)
│   │   ├── authors/        # AuthorCommandService, AuthorViewService
│   │   ├── books/          # BookCommandService, BookViewService
│   │   └── ...
│   ├── mappers/            # Маппинг DTO ↔ Forms
│   ├── dto/                # DTO результатов обработки форм
│   └── adapters/           # Адаптеры для Yii2 (PagedResultDataProvider)
├── commands/                # Console контроллеры (SeedController)
├── config/                  # Конфигурация Yii2
├── messages/                # Переводы i18n (ru-RU, en-US)
└── migrations/              # Миграции БД
```

**Примечание:** В коде используется namespace `app\`, что соответствует стандартному Yii2 алиасу `@app`. Структура директорий в корне проекта соответствует namespace-ам (например, `application/` → `app\application\*`).



### 13. Компромиссы Clean-ish архитектуры

Проект следует принципам **Clean Architecture**, но с осознанными компромиссами для Yii2, что делает его **Clean-ish** (не строго Clean, но близко к идеалу). Все компромиссы приняты намеренно для баланса между чистотой архитектуры и практичностью работы с Yii2.

#### 13.1. Domain Layer минимален

**Компромисс:** доменный слой намеренно минимален — бизнес-операции выполняются через Use Cases и порты, а ActiveRecord остается источником данных и правил валидации на уровне инфраструктуры.

**Почему:** в строгой Clean Architecture доменные сущности были бы чистыми PHP классами без зависимостей от фреймворка. Для Yii2 это означало бы тяжелый маппинг между доменными объектами и ActiveRecord моделями, что усложнило бы код без существенной пользы.

**Что получили:** 
* Use Cases остаются независимыми от фреймворка
* ActiveRecord используется только в Infrastructure layer
* Доменные события (`BookCreatedEvent`) обеспечивают decoupling (развязку)
* Бизнес-логика изолирована в Use Cases

#### 13.2. Репозитории используют ActiveRecord для запросов

**Компромисс:** репозитории используют `ActiveDataProvider` и ActiveRecord для выполнения запросов (сохранение eager loading через `with()`), но возвращают чистые DTO вместо моделей.

**Почему:** Yii2 ActiveRecord предоставляет мощные возможности оптимизации запросов (eager loading, оптимизация N+1 проблем), которые сложно реализовать вручную без потери производительности.

**Что получили:**
* Application layer получает чистые DTO без зависимостей от фреймворка
* Сохраняются все преимущества Yii2 ActiveRecord (eager loading, оптимизация запросов)
* Репозитории создают чистый `PaginationDto` вместо передачи framework-объектов
* В presentation layer адаптер преобразует `PaginationDto` обратно в `yii\data\Pagination` для виджетов

#### 13.3. Использование Yii2 компонентов в слое Presentation

**Компромисс:** слой Presentation использует Yii2 компоненты (`ActiveForm`, `DataProvider`, `Response`). Контроллеры содержат HTTP-логику и AJAX-валидацию (`ActiveForm::validate`), а Presentation Services — подготовку данных и вызов Use Cases.

**Почему:** Yii2 предоставляет мощные компоненты для работы с формами, пагинацией и HTTP, которые сложно заменить без потери функциональности и удобства разработки.

**Что получили:**
* Контроллеры — тонкие координаторы с HTTP-логикой (AJAX-валидация, редиректы)
* Presentation Services — подготовка данных для view и вызов Use Cases
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

<details open>
<summary><b>⚡ Quick Start (3 команды)</b></summary>

```bash
# 1. Клонируем проект
git clone https://github.com/WarLikeLaux/yii2-book-catalog.git
cd yii2-book-catalog

# 2. Поднимаем всё одной командой
make init

# 3. Готово! 🎉
open http://localhost:8000
```

</details>

> 💡 **Что делает `make init`:**
> - 🐳 Поднимает Docker контейнеры (PHP 8.4 + MySQL 8 + Queue Worker)
> - 📦 Устанавливает Composer зависимости
> - 🗄 Применяет миграции БД
> - 🌱 Наполняет базу демо-данными

<details>
<summary><b>🔑 Тестовые учётные данные</b></summary>

| Логин | Пароль |
|-------|--------|
| `admin` | `admin` |
| `demo` | `demo` |

</details>

Приложение будет доступно по адресу: **[http://localhost:8000](http://localhost:8000)**

### 🧪 Тестирование и покрытие кода

<table>
<tr>
<td align="center"><b>252</b><br>Tests</td>
<td align="center"><b>549</b><br>Assertions</td>
<td align="center"><b>100%</b><br>Coverage</td>
<td align="center"><b>~2s</b><br>Runtime</td>
</tr>
</table>

### 🛠 Команды проверки

| Команда | Описание | Назначение |
|---|---|---|
| `make test` | 🧪 Запуск всех тестов | **Testing** (Unit + Func) |
| `make test-coverage` | 📊 Отчет о покрытии (HTML) | **Testing** (Metric) |
| `make test-unit` | ⚡ Unit-тесты (без БД) | **Testing** (Speed) |
| `make test-functional` | 🌐 Functional-тесты (с БД) | **Testing** (Integration) |
| `make analyze` | 🔍 PHPStan (Level 9 + Strict) | **Quality** (Static Analysis) |
| `make deptrac` | 🏗 Архитектурный контроль | **Quality** (Architecture) |
| `make rector` | ♻️ Автоматический рефакторинг | **Quality** (Refactoring) |
| `make lint-fix` | 🧹 PHPCS (Auto-fix) | **Quality** (Style) |
| `make audit` | 🛡 Проверка зависимостей | **Security** (Vulnerabilities) |

<details>
<summary><b>📋 Структура тестов</b></summary>

| Тип | Количество | Описание |
|-----|------------|----------|
| **Unit** | 194 | Чистая бизнес-логика без БД и фреймворка |
| **Functional** | 55 | CRUD, API, Use Cases, HTTP-сценарии с БД |

**Unit Tests покрывают:**
- **Application Layer**: UseCases, Commands, UseCaseExecutor, QueryResult, PaginationRequest, IdempotencyService
- **Domain Layer**: Value Objects (`Isbn`, `BookYear`), Domain Events
- **Infrastructure**: Queue jobs (retry logic), Logger, Notifications
- **Presentation**: Validators, Mappers, DataProvider adapters

**Functional Tests покрывают:**
- API Идемпотентность (Idempotency-Key)
- Web-формы Идемпотентность
- REST API (Книги)
- CRUD операции (Книги, Авторы)
- Use Cases с реальной БД
- Валидация форм
- Аутентификация и авторизация

**Принципы тестирования:**
- ✅ Не тестируем функции фреймворка (`rules()`, `attributeLabels()`, `tableName()`)
- ✅ Unit-тесты изолированы от БД и внешних сервисов (mocking)
- ✅ `@codeCoverageIgnore` на методах требующих integration тестов
- ✅ Исключены из coverage: controllers, forms, views, AR models (покрыты functional тестами)

</details>

> 📈 **Отчет о покрытии:** `make test-coverage` → `tests/_output/coverage/index.html`


### 🛠 Основные команды

| Группа | Команда | Описание |
| :--- | :--- | :--- |
| **🚀 Setup** | `make init` | Полная инициализация проекта |
| | `make configure` | Настройка окружения (.env) |
| **🐳 Docker** | `make up` / `make down` | Запуск и остановка окружения |
| **📦 Data** | `make seed` | Наполнение базы демо-данными |
| **🧪 Quality** | `make dev` | **Автофикс + проверка (разработка)** |
| | `make ci` | Быстрая проверка (lint, analyze, test) |
| | `make pr` | Полная проверка перед PR (+ deptrac, infection, audit) |
| | `make fix` | Автоисправление (lint-fix + rector-fix) |
| **🔍 Debug** | `make logs` | Просмотр логов всех сервисов |
| | `make comments` | Показать TODO и заметки |
| | `make sms-logs` | Логи отправки SMS (Mock-сервис) |
| | `make shell` | Доступ в консоль PHP-контейнера |
| **📜 API** | `make swagger` | Генерация OpenAPI документации |
| **🚀 Performance** | `make load-test` | Запуск нагрузочного теста (k6) |

## ⚙️ Конфигурация

Настройки окружения находятся в файле `.env`.
*   `SMS_API_KEY`: Установите `MOCK_KEY` для эмуляции отправки (запись в лог) или реальный ключ.

---

<div align="center">

### 📊 Статистика проекта

![Source Code](https://img.shields.io/badge/Source_Code-4.5k+-blue?style=for-the-badge&logo=icloud&logoColor=white)
![Test Code](https://img.shields.io/badge/Test_Code-5.5k+-blue?style=for-the-badge&logo=codecov&logoColor=white)
![Source Files](https://img.shields.io/badge/Source_Files-135-purple?style=for-the-badge&logo=php&logoColor=white)
![Test Files](https://img.shields.io/badge/Test_Files-74-orange?style=for-the-badge&logo=codecov&logoColor=white)
![Test Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen?style=for-the-badge&logo=codecov&logoColor=white)
![PHPStan](https://img.shields.io/badge/PHPStan-Level_9_+_Strict-brightgreen?style=for-the-badge&logo=probot&logoColor=white)

<br>

**Made with ❤️ using [Yii2 Framework](https://www.yiiframework.com/)**

*Clean-ish Architecture • DDD • CQRS • Event-Driven*

</div>
