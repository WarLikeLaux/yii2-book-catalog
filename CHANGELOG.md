# Список изменений (Changelog)

[← Назад в README](README.md)

Все значимые изменения в этом проекте документируются в данном файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [0.9.0] - 2025-12-28

### 🚀 Новые функции и возможности
- **#18** - реализована **HTTP Idempotency** через заголовок `Idempotency-Key` для защиты от дублирования запросов ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - добавлен `IdempotencyFilter` для автоматического кеширования ответов POST-запросов ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))

### 🧪 Тестирование
- **#18** - достигнуто **100% покрытие кода тестами** (238 тестов, 517 assertions) ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - добавлены unit-тесты: `IdempotencyServiceTest`, `BookReadDtoTest`, `SubscribeUseCaseTest`, `YiiTransactionAdapterTest`, `IdempotencyFilterTest`, `LoginPresentationServiceTest` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - добавлены functional-тесты: `IdempotencyCest`, расширены `AuthorRepositoryTest`, `BookRepositoryTest` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - унифицированы аннотации `@codeCoverageIgnore` с русскими пояснениями ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))

### 🛠 Рефакторинг и архитектура
- **#18** - рефакторинг Makefile: новые команды `make dev`, `make ci`, `make pr`, `make fix` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - удалены избыточные `@codeCoverageIgnoreStart/End` блоки в репозиториях ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - упрощена конфигурация CI — coverage берётся из `codeception.yml` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))

### 📝 Документация
- **#18** - обновлен README: актуальная статистика (238 тестов, 100% coverage), новые команды ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - обновлен `contract.md`: добавлены команды `make dev/ci/pr/fix` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))

## [0.8.0] - 2025-12-27

### 🚀 Новые функции и возможности
- **#17** - реализован **REST API** для книг с поддержкой OpenAPI спецификации ([4ac7aa2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4ac7aa2))
- **#17** - внедрена автоматическая генерация документации Swagger и настроены заголовки безопасности (HSTS, CSP, X-Frame-Options) ([4ac7aa2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4ac7aa2))

### 🛠 Рефакторинг и архитектура
- **#16** - внедрен **Rector** для автоматического рефакторинга под стандарты **PHP 8.4** (readonly классы, типизация) ([9351974](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9351974))
- **#16** - обновлен `composer.json` для поддержки PHP 8.4 и стабилизации зависимостей ([ce50a44](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ce50a44))
- **#15** - оптимизирован CI пайплайн: добавлено кеширование зависимостей Composer ([f5eb0fa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f5eb0fa))

### ⚙️ Инфраструктура и надежность
- **#17** - добавлен нагрузочный тест (**k6**) для проверки производительности API ([4ac7aa2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4ac7aa2))
- **#16** - исправлена конфигурация хоста **Selenium** в CI и удален конфликтующий модуль Yii2 из acceptance suite ([f27436e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f27436e))
- **#16** - настроен запуск фонового PHP-сервера и **Selenium** для полноценного выполнения приемочных тестов в CI ([0649d1e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0649d1e))
- **#16** - настроен запуск Infection с ограничением сьютов (`functional,unit`) для стабильности CI ([0376291](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0376291))
- **#15** - внедрен аудит безопасности (`composer audit`) в CI пайплайн ([206eb2f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/206eb2f))
- **#16** - исправлены и улучшены CI workflow файлы (синтаксис команд, workflow_dispatch) ([4661af4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4661af4))
- **#15** - добавлена команда `make check` для комплексной проверки качества (lint, analyze, test, audit) ([544e660](https://github.com/WarLikeLaux/yii2-book-catalog/commit/544e660))

### 🧪 Тестирование
- **#15** - улучшен **Mutation Score Indicator (MSI)** до **92%** за счет покрытия граничных случаев ([544e660](https://github.com/WarLikeLaux/yii2-book-catalog/commit/544e660))
- **#15** - исправлена загрузка переменных окружения (`.env`) в тестах ([5adf2ef](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5adf2ef))
- **#15** - удален сидинг базы данных из CI для предотвращения загрязнения тестовых данных ([d42971a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d42971a))

### 📝 Документация
- **#17** - обновлена автогенерируемая документация схемы БД, моделей и маршрутов ([ff0a75b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ff0a75b))
- **#16** - исправлена навигация и обработка внешних ссылок в документации ([cba78e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cba78e8), [47bc9e6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/47bc9e6))
- **#16** - обновлена статистика проекта и оформление команд в README ([1af7cdf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1af7cdf))
- **#16** - выполнен откат HTML-ссылок на Markdown для соблюдения политики безопасности GitHub ([904d466](https://github.com/WarLikeLaux/yii2-book-catalog/commit/904d466))
- **#15** - интегрированы архитектурные диаграммы и документация по безопасности ([17b0075](https://github.com/WarLikeLaux/yii2-book-catalog/commit/17b0075))

## [0.7.0] - 2025-12-27

### 🛠 Рефакторинг и архитектура
- **#14** - полное разделение Presentation Services на **Command Services** (Write) и **View Services** (Read) для всех контроллеров (Books, Authors, Subscriptions) ([fb0a11c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fb0a11c))
- **#14** - внедрение Value Objects (`Isbn`, `BookYear`) для инкапсуляции бизнес-правил валидации ([70df022](https://github.com/WarLikeLaux/yii2-book-catalog/commit/70df022))
- **#14** - устранение анти-паттерна "Supervisor Controller" и удаление монолитных FormPreparationService ([fb0a11c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fb0a11c))

### ⚙️ Инфраструктура и надежность
- **#14** - реализована **идемпотентность** отправки SMS (через Cache Lock) для защиты от дублей при ретраях очереди ([1564e15](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1564e15))
- **#14** - добавлены архитектурные комментарии (Technical Debt) касательно Transactional Outbox, Service Locator в Job-ах и Stateful адаптеров ([bcab899](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bcab899))

### 🧪 Тестирование
- **#14** - добавлено **100+ новых тестов**, покрытие кода выросло с **~76%** до **~88%** ([0458b42](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0458b42))
- **#14** - Unit-тесты для: YiiPsrLogger, Queue Jobs, User, Subscription, PagedResultDataProvider, AuthorSelect2Mapper, UseCaseExecutor (query), QueryResult, валидаторов (UniqueIsbn, AuthorExists, UniqueFio, Isbn), форм (BookForm, SubscriptionForm, ReportFilterForm)
- **#14** - Functional-тесты для: CRUD Book/Author, Use Cases (Update/Delete Book, Author Use Cases), SubscriptionController, SiteController, SubscriptionViewService
- **#14** - исправлен баг в `UpdateBookUseCase` — добавлены недостающие импорты Value Objects (`BookYear`, `Isbn`)

### 📝 Документация
- **#14** - обновлен README: актуализирована структура проекта, описано разделение сервисов и использование DDD Value Objects ([a83f74d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a83f74d))
- **#14** - обновлена статистика тестов в README: 161 тест, 287 assertions, ~88% покрытие ([28c4fd7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/28c4fd7))

## [0.6.0] - 2025-12-25

### 🚀 Новые функции и возможности
- **#12** - добавлена поддержка TranslatorInterface и адаптер YiiTranslatorAdapter для независимых переводов ([27378fb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/27378fb))
- **#12** - добавлен сервис Selenium в docker-compose для приемочного тестирования ([77f05bd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/77f05bd))

### 🛠 Рефакторинг и архитектура
- **#12** - глобальный рефакторинг структуры проекта на слои Clean Architecture (application, domain, infrastructure, presentation) ([dba5729](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dba5729))
- **#12** - настроена инфраструктура покрытия кода (pcov) и внедрены строгие типизированные тесты с поддержкой локализации ([96c589b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/96c589b))

### ⚙️ Инфраструктура и очистка
- **#13** - удален конфигурационный файл .bowerrc ([ea559bb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ea559bb))
- **#13** - удалены устаревшие конфиги Vagrant и сопутствующие файлы ([87b4f20](https://github.com/WarLikeLaux/yii2-book-catalog/commit/87b4f20))
- **#12** - удален устаревший скрипт yii.bat и легаси загрузчики консоли ([0f5256d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0f5256d), [ba5840a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ba5840a))

### 🧪 Тестирование
- **#12** - добавлено unit-тестирование для UseCaseExecutor ([ba5840a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ba5840a))
- **#12** - внедрено покрытие кода и отчеты в формате HTML ([96c589b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/96c589b))

### 📝 Документация
- **#13** - обновлен README: детальное описание тестирования, команд Makefile и отчетов о покрытии ([627d5d6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/627d5d6))
- **#13** - обновлен README: разъяснение независимости слоя Application и использования портов ([5eec513](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5eec513))
- **#13** - обновлен README: отражены изменения в неймспейсах форм ([21671f3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/21671f3))

## [0.5.0] - 2025-12-22

### 🚀 Новые функции и возможности
- **#10** - созданы DTO результаты для форм (BookCreateFormResult, BookUpdateFormResult) и обновлены сервисы подготовки форм ([60325bb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/60325bb))
- **#8** - реализован UseCaseExecutor для стандартизированного выполнения бизнес-логики с обработкой ошибок и уведомлениями ([f6926ee](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f6926ee))
- **#8** - внедрена поддержка параметров пагинации в BookQueryService и BookSearchCriteria ([4224167](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4224167))
- **#8** - добавлена поддержка динамического кеширования схемы БД ([ca9e91e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ca9e91e))

### 🛠 Рефакторинг и архитектура
- **#8** - рефакторинг контроллеров (Author, Book, Site) для использования Presentation Services и View Data ([a3ce4dc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a3ce4dc), [862246a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/862246a), [387aad3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/387aad3))
- **#8** - рефакторинг системы уведомлений: перенос интерфейсов в порты приложения ([9aecbae](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9aecbae))
- **#8** - внедрение интерфейса DomainEvent и рефакторинг публикации событий в Use Cases ([a62c364](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a62c364))
- **#8** - рефакторинг команд создания/обновления книг: удаление зависимости от UploadedFile ([355747d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/355747d))
- **#8** - рефакторинг AuthorQueryService и BookQueryService с использованием QueryResultInterface ([45b0d8e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/45b0d8e))

### 🧪 Тестирование
- **#9** - добавлены функциональные тесты для AuthorCest, BookCest, ReportCest и SubscriptionCest ([5cd8426](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5cd8426))
- **#9** - добавлены функциональные тесты для Use Cases (CreateBook, Subscribe) ([7bd8cdb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7bd8cdb))
- **#9** - обновлен Makefile: добавлены команды для запуска тестов и настройки тестовой БД ([5cd8426](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5cd8426))

### 📝 Документация
- **#11** - обновлен README: описание архитектуры "Clean-ish", компромиссы и структура слоев ([1b19439](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1b19439))
- **#11** - обновлен README: описание изменений в DTO результатах ([8c63d1e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8c63d1e))
- **#9** - обновлен README: добавлены разделы про интеграционное и функциональное тестирование ([5db211f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5db211f), [137b0a7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/137b0a7))
- **#9** - обновлен README: документация по UseCaseExecutor, LoginPresentationService и обработке событий ([ac743f0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ac743f0), [8a14c7a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8a14c7a), [34f1e99](https://github.com/WarLikeLaux/yii2-book-catalog/commit/34f1e99), [f6279fe](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f6279fe))
- **#9** - обновлен README: разъяснены неймспейсы слоев и ответственность presentation services ([2a9feec](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2a9feec), [ae17838](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ae17838), [be9a8cf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/be9a8cf), [3bedce1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3bedce1))

## [0.4.0] - 2025-12-21

### 🛠 Рефакторинг и архитектура
- **#6** - рефакторинг приложения на использование паттернов Command, Query и Use Case ([463ce48](https://github.com/WarLikeLaux/yii2-book-catalog/commit/463ce48))
- **#6** - удаление старого слоя сервисов и внедрение новых форм ([463ce48](https://github.com/WarLikeLaux/yii2-book-catalog/commit/463ce48))
- **#6** - внедрение "богатых" моделей (Rich Models) для Author и Book ([e1f704a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e1f704a))
- **#7** - внедрение строгой типизации (strict types) во всем проекте ([95a7b25](https://github.com/WarLikeLaux/yii2-book-catalog/commit/95a7b25))

### ⚙️ Инфраструктура
- **#6** - улучшены проверки здоровья (health checks) в Docker Compose ([e1f704a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e1f704a))
- **#7** - обновлены правила PHPCS и улучшены зависимости проекта ([95a7b25](https://github.com/WarLikeLaux/yii2-book-catalog/commit/95a7b25))

### 📝 Документация
- **#7** - обновлен README ([de05984](https://github.com/WarLikeLaux/yii2-book-catalog/commit/de05984))

## [0.3.0] - 2025-12-04

### 🚀 Новые функции и возможности
- **#4** - внедрена модель BookSearch и интегрирована функциональность поиска в SiteController ([aacfa95](https://github.com/WarLikeLaux/yii2-book-catalog/commit/aacfa95))

### 📝 Документация
- **#5** - обновлен README: отражено изменение названия проекта и архитектурные улучшения ([79dea5e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/79dea5e))

### 🧹 Очистка
- **#4** - удалена лишняя пустая строка в файле миграции ([085f32b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/085f32b))

## [0.2.0] - 2025-12-03

### 🚀 Новые функции и возможности
- **#4** - добавлена поддержка PSR логирования для SMS сервисов и внедрен YiiPsrLogger ([9de1d48](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9de1d48))
- **#4** - реализована валидация ISBN и рефакторинг процесса создания книг ([94f7712](https://github.com/WarLikeLaux/yii2-book-catalog/commit/94f7712))
- **#4** - добавлен ReportService для получения отчетов по топ-авторам ([68e65eb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/68e65eb))
- **#4** - интегрирован виджет Select2 для выбора авторов в формах книг ([0864273](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0864273))
- **#4** - реализован Fan-out паттерн в очереди: создание NotifySingleSubscriberJob для рассылок ([818b2f7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/818b2f7))
- **#4** - внедрена валидация и нормализация телефонных номеров (E164) через libphonenumber ([0959736](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0959736), [b906b7e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b906b7e))

### 🛠 Рефакторинг
- **#4** - рефакторинг контроллеров на использование специализированных форм (AuthorForm, BookForm) ([53c7a8e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/53c7a8e), [10f5f2e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/10f5f2e))
- **#4** - рефакторинг структуры лейаутов для улучшения читаемости ([897bedb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/897bedb))
- **#4** - рефакторинг NotifySubscribersJob для использования модели подписки ([7e5ac1a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7e5ac1a))

### ⚙️ Инфраструктура
- **#4** - добавлен расширенный стандарт кодирования (Slevomat) и обновлены правила линтера ([3ab286a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3ab286a))
- **#4** - настроена тестовая база данных в Makefile и переменные окружения ([2df3cb9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2df3cb9), [64d0e1b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/64d0e1b))
- **#4** - добавлена команда lint-fix в Makefile ([a59739f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a59739f))
- **#4** - стандартизирован синтаксис массивов ([730da45](https://github.com/WarLikeLaux/yii2-book-catalog/commit/730da45))

## [0.1.0] - 2025-12-02

### 🚀 Новые функции и возможности
- **#2** - реализована базовая система каталога книг: CRUD авторов и книг, воркфлоу подписок ([cc58972](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cc58972))
- **#2** - добавлена консольная команда сидирования (seed) демо-данных ([cc58972](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cc58972))

### ⚙️ Инфраструктура
- **#1** - инициализация проекта на базе Yii2 Basic и PHP 8.4 ([3beeee3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3beeee3))
- **#1** - настройка Docker Compose: сервисы php, db, queue ([f84d646](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f84d646))
- **#1** - обновление зависимостей composer ([9e993bf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9e993bf))
- **#3** - создан Makefile для управления проектом ([78fca65](https://github.com/WarLikeLaux/yii2-book-catalog/commit/78fca65))

### 📝 Документация
- **#3** - начальная версия README с описанием архитектуры и инструкциями по установке ([49c1a3c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/49c1a3c), [283adf2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/283adf2))