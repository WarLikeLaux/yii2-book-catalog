# Список изменений (Changelog)

[← Назад в README](README.md)

Все значимые изменения в этом проекте документируются в данном файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.22.0] - 2026-03-21 - "Строгая типизация, CRUD-фильтрация и архитектурное упрощение"

> Масштабный рефакторинг с фокусом на строгую типизацию и упрощение архитектуры. Введён Value Object `AuthorId`, реализованы сортировка и фильтрация по колонкам в CRUD-интерфейсах. Полностью удалена инфраструктура трейсинга (Jaeger/OpenTelemetry) - 7 декораторов, фабрики и конфиги. Упрощены command handlers через UseCases-реестры, удалены `BookViewModel` и `PagedResultDataProviderFactory`. Оптимистичная блокировка перенесена в `save()`. Unit-тесты переведены на PHPUnit. Ошибки файлового хранилища выделены в отдельную иерархию `StorageException`.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#41** - добавлен Value Object `AuthorId` с валидацией, `equals()` и `Stringable`; доменные сущности (`Book`, `Subscription`) переведены на `AuthorId` вместо `int[]`; `AuthorIdCollection` обновлён на `AuthorId[]` с `toIntArray()`; инфраструктурные репозитории обновлены для маппинга `AuthorId` ↔ `int` ([9f0fffd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9f0fffd), [abe2021](https://github.com/WarLikeLaux/yii2-book-catalog/commit/abe2021), [0a5e264](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0a5e264), [6c548f7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6c548f7))
- **#41** - добавлены `StorageErrorCode` enum и `StorageException` для иерархии ошибок файлового хранилища ([3dfe9f7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3dfe9f7))
- **#41** - реализована сортировка: `SortDirection` enum, `SortRequest` DTO, `applySortToQuery()` в `BaseQueryService`; добавлено извлечение параметров сортировки в `BookListViewFactory` и `AuthorListViewFactory` ([8e4344e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8e4344e), [dd098a1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dd098a1), [12a0e81](https://github.com/WarLikeLaux/yii2-book-catalog/commit/12a0e81))
- **#41** - добавлена фильтрация по колонкам через `searchWithFilters` в query service портах; созданы форм-модели фильтров для книг и авторов с view model и фабриками ([9cfc1fe](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9cfc1fe), [ac64cdb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ac64cdb))
- **#41** - добавлены `BookUseCases`, `AuthorUseCases` и `CoverUploadService` для агрегации сценариев ([e856f60](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e856f60))
- **#41** - добавлена обработка ошибок с логированием в `YiiEventPublisherAdapter` и `YiiTransactionAdapter` ([d9981c2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d9981c2))

### 🐛 Исправления

- **#41** - исправлен тихий fallback в `ReportsConfig`: заменён на `ConfigurationException`, добавлены константы `MIN_CACHE_TTL`/`MAX_CACHE_TTL` ([a0bc77e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a0bc77e))
- **#41** - исправлен `buildConditionFor()` для обработки вложенных `CompositeAnd`/`CompositeOr`, тихий null заменён на `InvalidArgumentException` ([0a419c8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0a419c8))
- **#40** - исправлены `excludePaths` PHPStan после переноса тестов, добавлена очистка кэша в `make analyze` ([18c599e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/18c599e))

### 🛠 Рефакторинг и архитектура

- **#41** - удалена инфраструктура трейсинга: 7 декораторов, `TracerInterface`, `OtelTracer`, `NullTracer`, `TracingFactory`, `TracingMiddleware`, `JaegerConfig`; удалён контейнер Jaeger и пакеты OpenTelemetry ([856aef6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/856aef6), [3628189](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3628189), [ba023cf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ba023cf))
- **#41** - удалены `BookViewModel` и `BookViewModelMapper`, views и handlers используют `BookReadDto` напрямую ([76e992f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/76e992f))
- **#41** - рефакторинг оптимистичной блокировки: проверка версии перенесена в `save(expectedVersion)`, удалены `incrementVersion()` и `getByIdAndVersion()` ([6eacac8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6eacac8), [7e28cc1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7e28cc1), [b3d4b93](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b3d4b93))
- **#41** - перемещены `FileContent` и `FileKey` из `domain/values/` в `application/common/values/` ([0bb4f8a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0bb4f8a))
- **#41** - рефакторинг `AuthorIdCollection`: удалён `fromMixed()`, `fromArray()` стал строгим с `ValidationException`; `BookForm` использует явное приведение к `int` ([9d286f2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9d286f2), [fd4eef1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fd4eef1))
- **#41** - упрощены `BookCommandHandler` (8 → 4 параметра) и `AuthorCommandHandler` (5 → 3 параметра) через `UseCases`-реестры ([531128c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/531128c), [9d7b7a4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9d7b7a4))
- **#41** - удалён `PagedResultDataProviderFactory`, handlers используют `PagedResultDataProvider` напрямую ([7badf25](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7badf25))
- **#41** - рефакторинг `Isbn`: magic numbers заменены на именованные константы ([358c064](https://github.com/WarLikeLaux/yii2-book-catalog/commit/358c064))
- **#41** - добавлена константа `MAX_PORT_NUMBER` в `ApiPageConfig`, заменён magic number 65535 ([8067fd0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8067fd0))
- **#41** - рефакторинг ошибок файлового хранилища из `DomainErrorCode` в `StorageException`, удалены 5 кейсов из доменного enum ([2243aef](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2243aef))
- **#41** - рефакторинг расположения пунктов меню в основном layout ([0fa458e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0fa458e))
- **#40** - рефакторинг `ApiInfoViewModel`: сырые порты заменены на готовые URL, добавлена поддержка HTTPS ([25e652e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/25e652e))
- **#40** - стандартизировано форматирование views: breadcrumbs и HTMX load-more блок ([11092b0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/11092b0))

### 🧪 Тестирование

- **#40** - миграция unit-тестов на PHPUnit: удалены Yii2-зависимости, integration-тесты перемещены в отдельную сьюту ([d75173f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d75173f), [a2977a8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a2977a8), [a74246e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a74246e))
- **#41** - добавлены тесты обработки ошибок в `YiiEventPublisherAdapter` и `YiiTransactionAdapter` ([bec2332](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bec2332))
- **#41** - добавлены тесты для вложенных composite specifications и null-skip ветвей в `buildConditionFor()` ([2b5eb41](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2b5eb41))
- **#41** - добавлены тесты `applySortToQuery` и сортировки в `PagedResultDataProvider` ([7a83d20](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7a83d20))
- **#41** - обновлены тесты для `AuthorId` VO, `StorageException`, `BookReadDto`, `FileContent`/`FileKey` и удаления `PagedResultDataProviderFactory` ([1c8b16a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1c8b16a), [0d73e05](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0d73e05), [dfff2d6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dfff2d6), [dc5ad22](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dc5ad22), [471b4b8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/471b4b8), [e6a498e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e6a498e))
- **#40** - добавлена конфигурация source directory в phpunit.xml.dist ([11a97fb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/11a97fb))

### 📝 Документация

- **#41** - обновлена документация: удалены ссылки на Jaeger/OpenTelemetry/tracing из ARCHITECTURE, README, COMPARISON, learning docs, deptrac ([ecb458b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ecb458b))
- **#41** - добавлены учебные материалы и структурированная документация по архитектуре проекта ([ed5c4d3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ed5c4d3), [6658775](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6658775))
- **#41** - обновлена документация проекта ([ca48b20](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ca48b20))
- **#41** - обновлены метрики тестов в README ([1567156](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1567156))
- **#41** - обновлены инструкции workflow и инспекции кода ([0ab41a3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0ab41a3), [f392e6a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f392e6a))
- **#41** - обновлена документация навыков: подходы аудита кода, типографика, методология ([b32c30b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b32c30b), [d51f433](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d51f433), [2a46d7a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2a46d7a))
- **#39** - добавлена запись CHANGELOG v0.21.0 ([d43a955](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d43a955))

### ⚙️ Инфраструктура

- **#41** - добавлены настройки concurrency в CI workflow ([232005d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/232005d))
- **#41** - добавлены навыки badges, docs, readme и humanize для проверки AI-трафаретов ([3894075](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3894075), [f5c76d4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f5c76d4))
- **#41** - удалены устаревшие навыки (commit-staged, discuss, migrate, test), обновлены commit, docs, hunt ([e2cf69d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e2cf69d))
- **#39** - запущен CI ([42b4386](https://github.com/WarLikeLaux/yii2-book-catalog/commit/42b4386))

</details>

## [0.21.0] - 2026-03-09 - "PHP 8.5, инфраструктурная зрелость и CI на Docker Compose"

> Масштабная миграция на PHP 8.5 с обновлением всех зависимостей, Rector-правил и PHPStan-конфигов. CI-пайплайн полностью переведён на Docker Compose через Makefile-таргеты. Лицензия изменена с BSD-3-Clause на MIT. Внедрена библиотека oscarotero/env вместо кастомной функции env(). Стандартизированы em dash на дефисы по всему проекту. Добавлена поддержка protobuf для OTLP-экспорта OpenTelemetry. Инфраструктура переведена на named volumes, навыки AI-агента перенесены в .claude/skills.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#39** - добавлено расширение protobuf для OTLP-экспорта OpenTelemetry ([72f5af4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/72f5af4))
- **#39** - добавлен POSTGRES_USER в .env.example ([a784bd7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a784bd7))

### 🐛 Исправления

- **#39** - исправлена совместимость с PHPUnit 13: замена удалённого isType() на callback(), исключение не-exception классов в arkitect-правиле ([2376c6c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2376c6c))
- **#39** - исправлены ошибки PHPStan: невалидные named args в AlreadyExistsException, mixed return type в PagedResultDataProvider::prepareKeys() ([2c87b65](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2c87b65))
- **#39** - исправлен CI: переключение на createUnsafeImmutable для работы Env::get через getenv() ([d1c26f4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d1c26f4))
- **#39** - исправлен git safe.directory для использования system-wide конфига ([144b016](https://github.com/WarLikeLaux/yii2-book-catalog/commit/144b016))
- **#39** - исправлено CI-окружение: добавлены UID/GID в ci-env, добавлен --build в ci-up ([f280b0a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f280b0a))

### 🛠 Рефакторинг и архитектура

- **#39** - обновлён проект до PHP 8.5 со всеми composer-зависимостями ([dcb1354](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dcb1354))
- **#39** - рефакторинг кода с Rector PHP 8.5: array_any(), new без скобок, #[Override], удалён deprecated curl_close() ([11730bb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/11730bb))
- **#39** - удалён deprecated finfo_close() для совместимости с PHP 8.5 ([4f35854](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4f35854))
- **#39** - рефакторинг arkitect exception rule: фильтрация по Throwable вместо захардкоженных имён классов ([3abc5f5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3abc5f5))
- **#39** - заменена кастомная функция env() на библиотеку oscarotero/env (Env::get) ([80b7f26](https://github.com/WarLikeLaux/yii2-book-catalog/commit/80b7f26))
- **#39** - стандартизировано em dash на дефис в коде и конфигах ([a77fc86](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a77fc86))
- **#39** - стандартизировано em dash на дефис в документации ([190e765](https://github.com/WarLikeLaux/yii2-book-catalog/commit/190e765))
- **#39** - стандартизировано em dash на дефис в определениях навыков ([cc5118f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cc5118f))
- **#39** - удалены избыточные вызовы setAccessible(true) (не нужны с PHP 8.1) ([e880dea](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e880dea))

### 🧪 Тестирование

- **#39** - добавлен тест для OtelTracer::flush() и удалён setAccessible(true) ([fb9efa0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fb9efa0))

### ⚙️ Инфраструктура

- **#39** - рефакторинг CI workflow на Docker Compose через Makefile-таргеты ([b464d9d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b464d9d))
- **#39** - обновлён CI на PHP 8.5 ([82856e0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/82856e0))
- **#39** - обновлены конфиги Rector и PHPStan на PHP 8.5 ([e9df95a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e9df95a))
- **#39** - обновлена лицензия с BSD-3-Clause на MIT ([ffc9d38](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ffc9d38))
- **#39** - обновлён deptrac/deptrac с 4.x-dev до стабильного ^4.6 ([142744d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/142744d))
- **#39** - упрощён Makefile: удалены git-ярлыки, очищены заголовки секций, реорганизованы таргеты ([c19bda7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c19bda7))
- **#39** - обновлён make help: объединены QA и dev секции, добавлены недостающие таргеты, удалён pr таргет ([2f8116f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2f8116f))
- **#39** - переключение на named volumes для данных БД, удалены ссылки на db-data из тулинга ([60bd254](https://github.com/WarLikeLaux/yii2-book-catalog/commit/60bd254))
- **#39** - обновлены метаданные composer.json: name, description, homepage, keywords ([cde98f2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cde98f2))
- **#39** - перенесены конфиги тестов PHPStan-правил в tests/unit/infrastructure/phpstan/ ([a62098e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a62098e))
- **#39** - добавлен use import для класса Application ([5f01cb7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5f01cb7))
- **#39** - удалён package.json: все скрипты дублировали Makefile-таргеты ([7080129](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7080129))
- **#39** - удалены скрипты PR review, навык и связанный конфиг ([4a3859b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4a3859b))
- **#39** - удалён npm из Dockerfile (nodejs достаточно) ([6a7a944](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6a7a944))
- **#39** - перенесены навыки из .agent/skills в .claude/skills ([5755a04](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5755a04))
- **#39** - упрощён repomix.config.json: удалены правила, уже покрытые .gitignore ([1497a03](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1497a03))
- **#39** - переименован test-db-fresh в db-test-fresh ([f29a47e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f29a47e))
- **#39** - переименован docs/auto в docs/generated ([6d335b9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6d335b9))
- **#39** - добавлен upload E2E-артефактов в CI, добавлены runtime-директории в ci-env ([16fe99c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/16fe99c))

### 📝 Документация

- **#39** - обновлена документация: PHP 8.4 → 8.5, метрики тестов 1007 тестов / 2479 assertions ([82c8a0c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/82c8a0c))
- **#39** - обновлена landing page: PHP 8.5, уточнены описания trade-off ([96531f1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/96531f1))
- **#39** - обновлена дата lastmod в sitemap ([14a475a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/14a475a))
- **#39** - обновлены метрики тестов в README: 1008 тестов, 2480 assertions ([16fe99c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/16fe99c))

</details>

## [0.20.0] - 2026-02-23 - "Доменная чистота, pull-модель событий и архитектурная зрелость"

> Грандиозный архитектурный релиз, поднявший проект на качественно новый уровень DDD-чистоты. Реализована pull-модель доменных событий через RecordsEvents trait. Репозиторные интерфейсы перенесены в домен, хранилище файлов — в адаптеры. BookSearchSpecificationFactory вынесена из домена в application. Вычленён независимый CoverKeysScanner из BookQueryService. Внедрены inline invariant guards в агрегат Book, безопасное удаление авторов с FK RESTRICT-миграциями и проверками использования. Проведена масштабная стандартизация: FQCN-импорты в конфигах, BookStatus enum вместо magic strings, Phone VO, PhoneMasker, checker-интерфейсы. Система навыков AI-агента полностью переработана — audit разделён на hunt и reflect. Инфраструктура усилена Jaeger/OTel observability, health check endpoint и архитектурными правилами PHPArkitect.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#38** - добавлен `Auth` guard в контроллер здоровья системы ([e04946d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e04946d))
- **#38** - реализована pull-модель доменных событий через **RecordsEvents trait** в сущности Book ([9c8ab98](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9c8ab98))
- **#38** - рефакторинг use cases и репозитория для публикации событий из сущности ([a68ee7f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a68ee7f))
- **#38** - добавлен **EventSerializer** в infrastructure, удалён getPayload из DomainEvent ([12383cb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/12383cb))
- **#38** - добавлены inline invariant guards в мутации агрегата Book (updateDescription, updateCover, removeAuthor) ([44722a0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/44722a0))
- **#38** - реализовано безопасное удаление авторов с проверками использования и removeAllBookLinks ([7f8d798](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7f8d798))
- **#38** - добавлен порт **AuthorUsageCheckerInterface**, новые DomainErrorCode и контракт removeAllBookLinks ([044df7c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/044df7c))
- **#38** - добавлены RESTRICT FK миграции для book_authors и subscriptions ([14c07d3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/14c07d3))
- **#38** - добавлен порт **CoverKeysScannerInterface** и реализация CoverKeysScanner ([2e771c4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e771c4))
- **#38** - добавлен **Phone** value object, валидация в Subscription и StoredFileReference ([5395214](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5395214))
- **#38** - добавлен **PhoneNormalizerInterface** и LibPhoneNormalizer, извлечён RequestIdProviderInterface, замена magic strings на BookStatus enum в infrastructure ([85b549b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/85b549b))
- **#38** - добавлен **PhoneMasker** для маскирования в SMS-логировании ([839a8fc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/839a8fc))
- **#38** - добавлены checker-интерфейсы и реализации ([4d9eebf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4d9eebf))
- **#38** - добавлен **ApiPageConfig** ([dafeee0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dafeee0))
- **#38** - добавлен ApiPageConfig в ConfigFactory и DI-контейнер ([0abbfa6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0abbfa6))
- **#38** - добавлена дедупликация id в AuthorIdCollection ([bb884f1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bb884f1))
- **#38** - добавлен **UnexpectedDtoTypeException** для замены generic LogicException в BookListViewFactory ([54f4a52](https://github.com/WarLikeLaux/yii2-book-catalog/commit/54f4a52))
- **#38** - добавлено архитектурное правило **NoGhostQueryServiceInApplicationRule** (PHPArkitect) ([447a9ec](https://github.com/WarLikeLaux/yii2-book-catalog/commit/447a9ec))
- **#38** - реализован **CheckHealthUseCase** и CheckHealthCommand ([bd93991](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bd93991))
- **#38** - реализован health check endpoint и runners ([09283d8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/09283d8))
- **#38** - реализована конфигурация **Jaeger** и OTel observability сервисы ([184046e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/184046e))
- **#38** - добавлены skeleton card partial и catalog.js с toast-уведомлениями, модалкой подписки и GLightbox re-init ([fcc8577](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fcc8577))
- **#38** - добавлен **FormToBookCommandMappingListener**, удалён MapFrom из CreateBookCommand и UpdateBookCommand ([e3292a3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e3292a3))
- **#38** - добавлена валидация автора/подписки в usecases ([99083f8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/99083f8))

### 🐛 Исправления

- **#38** - исправлена валидация чтения файла в UploadedFileStorage ([7aadb52](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7aadb52))
- **#38** - исправлены FK RESTRICT тесты для PostgreSQL transaction abort state ([36c178f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/36c178f))
- **#38** - исправлен AuthorExistenceChecker для обработки дублированных ids в existsAllByIds ([895891a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/895891a))
- **#38** - исправлены ожидания теста дублированных author ids для нормализованного Book entity ([e1c7882](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e1c7882))
- **#38** - исправлен YiiAuthAdapter null check с instanceof ([7be560f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7be560f))
- **#38** - исправлены аннотации code coverage в DiskSpaceHealthCheck ([4b50d74](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4b50d74))
- **#38** - исправлена генерация swagger docs ([a15263b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a15263b))
- **#38** - исправлен list-comments: пропуск директорий и поддержка js/mjs ([3b7da0e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3b7da0e))
- **#38** - исправлен тест NoGhostQueryServiceInApplicationRule ([6f1372c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6f1372c))
- **#38** - исправлен тест HealthEndpointCest, падавший из-за отсутствия аутентификации ([b373aa8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b373aa8))
- **#38** - исправлено правило phparkitect для query DTO (добавлен запрет на app\application\common\services), заменён широкий глоб excludePath phpstan на конкретный файл ([594a53f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/594a53f))

### 🛠 Рефакторинг и архитектура

- **#38** - рефакторинг use cases авторов для атомарности транзакций и корректной обработки ошибок ([702be26](https://github.com/WarLikeLaux/yii2-book-catalog/commit/702be26))
- **#38** - перенесены репозиторные интерфейсы в домен, техническое хранилище — в адаптеры ([c11196e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c11196e))
- **#38** - перенесена **BookSearchSpecificationFactory** из домена в application layer ([29b3a5a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/29b3a5a))
- **#38** - удалён getReferencedCoverKeys из BookQueryServiceInterface ([2ba4965](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2ba4965))
- **#38** - рефакторинг архитектуры в паттерн директории src/ ([3d2d4ff](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3d2d4ff))
- **#38** - рефакторинг **FileContent**: перенесён fromPath в UploadedFileStorage, закрытие stream в finally ([66e4200](https://github.com/WarLikeLaux/yii2-book-catalog/commit/66e4200))
- **#38** - рефакторинг HealthController для использования HealthResponseFormatter и use case ([8efe53a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8efe53a))
- **#38** - рефакторинг AuthViewFactory: инжектирован ApiPageConfig для swagger/app портов ([8311fcf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8311fcf))
- **#38** - рефакторинг форм: удалена валидация из presentation layer ([75ff8a6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/75ff8a6))
- **#38** - рефакторинг usecases для использования existence и isbn checkers ([21bff6e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/21bff6e))
- **#38** - рефакторинг presentation: DomainErrorCode, field errors, SystemInfoWidget DI ([8b72e0d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8b72e0d))
- **#38** - рефакторинг use cases и репозиторий для публикации событий из сущности ([a68ee7f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a68ee7f))
- **#38** - замена magic status strings на **BookStatus enum** в presentation layer, инжектирован RequestIdProviderInterface в контроллеры ([968cba0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/968cba0))
- **#38** - рефакторинг ChangeBookStatusCommand для использования BookStatus enum, нормализация телефона в SubscribeUseCase ([826c9b8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/826c9b8))
- **#38** - замена raw array в портах на **IdempotencyRecordDto** и **RateLimitResult** ([1b7a52e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1b7a52e))
- **#38** - обновлены репозитории и декораторы для возврата DTO, введён ClockInterface ([628141e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/628141e))
- **#38** - введён **ClockInterface** в RateLimitFilter, FormToBookCommandMappingListener перенесён в presentation ([6e0237a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6e0237a))
- **#38** - введён DTO-only контракт для application/*/queries layer (PHPArkitect, Deptrac, docs) ([c876ba5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c876ba5))
- **#38** - удалён ghost BookQueryService и BookSearchCriteria из application layer ([e3dd776](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e3dd776))
- **#38** - удалена idempotency middleware и связанный код ([33d41ec](https://github.com/WarLikeLaux/yii2-book-catalog/commit/33d41ec))
- **#38** - удалены мёртвые DomainErrorCode cases (AuthorCreateFailed, AuthorUpdateFailed, SubscriptionCreateFailed, SubscriptionStaleData, MapperFailed) ([8c7e721](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8c7e721))
- **#38** - удалён phpstan из grumphp pre-commit ([ea7175b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ea7175b))
- **#38** - удалены Buggregator и Inspector observability сервисы ([66854c6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/66854c6))
- **#38** - упрощён CreateBookUseCase: удалена избыточная проверка bookId ([3b3e5bb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3b3e5bb))
- **#38** - стандартизованы FQCN-импорты в конфигах (use + short name) ([a679366](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a679366))
- **#38** - стандартизованы пространства имён тестов (app\\tests → tests) ([4be6cbf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4be6cbf))
- **#38** - перенесён AuthorIdCollectionTest из domain в application ([761befb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/761befb))
- **#38** - перенесён NativeMimeTypeDetectorTest из domain в infrastructure ([5ce5858](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5ce5858))
- **#38** - рефакторинг view templates: стандартизация отступов и форматирования ([150656d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/150656d))
- **#38** - упрощён guard публикации в Book::transitionTo: удалена избыточная проверка Draft статуса ([d870837](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d870837))
- **#38** - рефакторинг компонентов по результатам ревью PR (nitpicks) ([c4d52a5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c4d52a5))
- **#38** - удалены комментарии ([7f86320](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7f86320))
- **#38** - рефакторинг порядка валидации ISBN в use cases, добавлен #[Override] в RateLimitStorageTracingDecorator ([8e63982](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8e63982))

### 🧪 Тестирование

- **#38** - добавлены unit и integration тесты для безопасного удаления авторов ([e4fce35](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e4fce35))
- **#38** - добавлены тесты CoverKeysScanner и обновлены тесты декоратора BookQueryService ([0e09d60](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0e09d60))
- **#38** - добавлены тесты для inline invariant guards (updateDescription, updateCover, removeAuthor) ([a90486b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a90486b))
- **#38** - добавлен FormToBookCommandMappingListenerTest ([7fc9065](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7fc9065))
- **#38** - добавлен тест дублированных author ids для UpdateBookUseCase ([fccd7d7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fccd7d7))
- **#38** - добавлен integration тест целостности контейнера ([c558627](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c558627))
- **#38** - обновлены тесты и docs для доменных событий в entities ([c537a21](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c537a21))
- **#38** - обновлены тесты для рефакторинга checkers и новых сервисов ([48dfe68](https://github.com/WarLikeLaux/yii2-book-catalog/commit/48dfe68))
- **#38** - обновлены тесты для рефакторинга валидации ([f634dfa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f634dfa))
- **#38** - обновлены тесты для рефакторинга port array, добавлен IdempotencyRepositoryTracingDecoratorTest ([ae370ca](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ae370ca))
- **#38** - обновлены тесты phone и stored file reference для assert ожидаемых исключений ([c14c53b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c14c53b))
- **#38** - обновлён HealthControllerTest для новых зависимостей ([04fb108](https://github.com/WarLikeLaux/yii2-book-catalog/commit/04fb108))
- **#38** - добавлены тесты для убийства мутантов: дефолтный HTTP 422 для BusinessRuleException/ValidationException, multibyte mb_strlen в BookPublicationPolicy ([69a435f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/69a435f))
- **#38** - добавлены тесты stream lifecycle для UploadedFileStorage: проверка fclose в finally при успехе и исключении ([e40ecfa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e40ecfa))
- **#38** - исправлены невалидные ISBN-13 контрольные суммы в UpdateBookUseCaseCest, вызывавшие ValidationException вместо StaleDataException и EntityNotFoundException ([913024a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/913024a))
- **#38** - убиты мутанты: граничные значения портов в ApiPageConfig, early return в Book::changeYear ([c329abe](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c329abe))
- **#38** - добавлены тесты для 100% code coverage: нечитаемый файл в UploadedFileStorage, удаление с null ID в AuthorRepository, AuthorRepositoryTracingDecorator ([b65a50d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b65a50d))

### ⚙️ Инфраструктура

- **#38** - исправлен порядок переменных JAEGER_ в .env.example ([24d9a13](https://github.com/WarLikeLaux/yii2-book-catalog/commit/24d9a13))
- **#38** - заменён скилл audit на **hunt** и **reflect** ([bb546c2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bb546c2))
- **#38** - введена система skills и обновлены ссылки проекта ([dbc1186](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dbc1186))
- **#38** - мигрированы workflows в новую структуру skills ([62dc15e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/62dc15e))
- **#38** - обновлены deptrac layers и архитектурная документация ([745de4e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/745de4e))
- **#38** - обновлена конфигурация приложения и core зависимости ([695cd3e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/695cd3e))
- **#38** - обновлены rector команды в Makefile: добавлен --clear-cache ([2bd79be](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2bd79be))
- **#38** - добавлен autoload-dev для пространства имён tests ([672aaaf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/672aaaf))
- **#38** - перенесены PHPStan fixtures в tests, рефакторинг конфигурации ([e423a67](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e423a67))
- **#38** - добавлены .gitignore записи для make lock artifacts (.dev.lock, .test.lock) ([d430a95](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d430a95))
- **#38** - добавлены переводы для ошибок валидации phone и file ([b1a68b0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b1a68b0))
- **#38** - добавлен lang attributes в html mail layout ([990c49e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/990c49e))
- **#38** - добавлены переводы для ограничений удаления авторов (en-US, ru-RU) ([044df7c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/044df7c))
- **#38** - зарегистрированы AuthorUsageCheckerInterface и CoverKeysScannerInterface в DI-контейнере ([2e771c4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e771c4))
- **#38** - рефакторинг awk-команды в Makefile для улучшенного определения заголовков тегов ([71b63fe](https://github.com/WarLikeLaux/yii2-book-catalog/commit/71b63fe))
- **#38** - рефакторинг путей review-скриптов в bin ([f385aa2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f385aa2))
- **#38** - стандартизована конфигурация: добавлен заголовок секции MAILER, healthcheck для jaeger, унифицированы FQCN-импорты ([8634b2a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8634b2a))
- **#38** - обновлён скилл changelog: по умолчанию коммиты добавляются в последнюю версию, дата обновляется по свежему коммиту ([51cf737](https://github.com/WarLikeLaux/yii2-book-catalog/commit/51cf737))

### 📝 Документация

- **#38** - обновлена документация скиллов ИИ агента (migrate, test, make-no-mistakes) ([cf1a77c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cf1a77c))
- **#38** - обновлена документация ([a977c89](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a977c89))
- **#38** - обновлена документация ([96538fb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/96538fb))
- **#38** - удалён избыточный комментарий и обновлены docs ([cc7634e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cc7634e))
- **#38** - синхронизированы примеры CreateBookUseCase и ChangeBookStatusUseCase в COMPARISON.md с актуальным кодом ([cc4a0c7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cc4a0c7))
- **#38** - добавлен шаг make analyze в цикл разработки в документации ([f9b4303](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f9b4303))
- **#38** - обновлён go skill: уточнён workflow финальной проверки ([cc1025d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cc1025d))
- **#37** - обновлена архитектурная документация: добавлена визуализация жизненного цикла запроса ([af0031b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/af0031b))
- **#37** - синхронизирован лендинг с README: замена CQRS на CQS, обновлены метрики, добавлены карточки Status FSM, CAS Storage, Value Objects и Arkitect ([c20cc0b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c20cc0b))
- **#38** - синхронизированы примеры кода в COMPARISON.md и метрики E2E в README.md ([96d93ea](https://github.com/WarLikeLaux/yii2-book-catalog/commit/96d93ea))
- **#38** - исправлены расхождения примеров кода с реальной кодовой базой ([97c1b84](https://github.com/WarLikeLaux/yii2-book-catalog/commit/97c1b84))
- **#38** - обновлена документация ([7bfafcf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7bfafcf))
- **#38** - обновлена документация ([74a4e8b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/74a4e8b))
- **#38** - обновлён лендинг: синхронизированы метрики (903 теста, 2251 assertions), добавлены карточки Rich Domain Model/Observability/Health Check ([77768d5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/77768d5))
- **#38** - удалён llms.txt ([36c384b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/36c384b))
- **#38** - исправлены ссылки документации: замена ветки dev на main ([e0c6724](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e0c6724))

</details>

## [0.19.0] - 2026-02-08 - "Презентационная революция, декларативные ошибки и машина состояний"

> Масштабнейший релиз, охватывающий все слои приложения: реализован паттерн BaseController с ViewModelRenderer, внедрена декларативная система обработки доменных ошибок через ErrorMapping-атрибуты и DomainExceptionTranslationMiddleware. Введена машина состояний BookStatus с безопасными переходами. Presentation-слой полностью переработан — Read-side handlers переименованы в ViewFactory, контроллеры переведены на early returns. Формы мигрированы на constructor DI, репозитории стандартизированы через ActiveRecordHydrator и reconstitute(). Локализованы все представления, стандартизированы стили через CSS-классы, добавлена поддержка HTMX partial rendering.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#33** - реализована инфраструктура **ViewModel** и **BaseController** ([8984fe3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8984fe3))
- **#33** - добавлен **ViewModelRendererTest** ([78094eb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/78094eb))
- **#33** - реализован **ViewModelInterface** во всех ViewModels ([dee7679](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dee7679))
- **#33** - добавлен метод **loadFromRequest** в формы ([1475765](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1475765))
- **#33** - добавлен **ActionName enum** ([273b4eb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/273b4eb))
- **#33** - добавлены новые доменные значения, мапперы и UI-компоненты с unit-тестами ([109bba6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/109bba6))
- **#33** - реализована **локализация сообщений доменных исключений** ([f391679](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f391679))
- **#33** - создано кастомное правило Rector для многострочных аннотаций представлений ([d9d5ceb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d9d5ceb))
- **#33** - добавлен код ошибки **AuthInvalidCredentials** и обновлена сигнатура login() на void ([c1142cd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c1142cd))
- **#33** - добавлена **AJAX-валидация** и паттерн раннего возврата в AuthorController ([52b0dde](https://github.com/WarLikeLaux/yii2-book-catalog/commit/52b0dde))
- **#33** - реализована инфраструктура исключений с привязкой к полям (**field-aware exceptions**) ([ad15367](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ad15367))
- **#33** - реализован **middleware трансляции доменных исключений** ([f33d014](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f33d014))
- **#33** - представлен фабричный метод **Author::reconstitute()** с приватным конструктором ([a85568a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a85568a))
- **#34** - добавлен **ViewModelRenderer::renderPartial** для HTMX partial rendering ([67e50ed](https://github.com/WarLikeLaux/yii2-book-catalog/commit/67e50ed))
- **#34** - реализован **ErrorType enum** и атрибут **ErrorMapping**, добавлен #[ErrorMapping] ко всем DomainErrorCode ([d27ef60](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d27ef60))
- **#34** - добавлен **BusinessRuleException**, реализован **fromEnum()** в DomainErrorMappingRegistry ([52d8546](https://github.com/WarLikeLaux/yii2-book-catalog/commit/52d8546))
- **#36** - добавлен **BookStatus enum** с машиной состояний переходов и спецификациями status/composite-and ([927878e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/927878e))
- **#35** - добавлен доменный гвард: опубликованные книги всегда должны иметь хотя бы одного автора ([40426cd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/40426cd))
- **#36** - замена PublishBookUseCase на **ChangeBookStatusUseCase**, обновлен BookReadDto и use cases для BookStatus ([373e47a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/373e47a))
- **#36** - добавлены действия unpublish/archive/restore, обновлены views и i18n для BookStatus workflow ([9cc35c9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9cc35c9))
- **#36** - обновлена инфраструктура: BookStatus в repository, nullable job mapping, status-aware listener и search ([b279853](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b279853))
- **#35** - добавлены виджеты **BookStatusBadge** и **BookStatusActions**, рефакторинг views ([f9f37ef](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f9f37ef))
- **#35** - добавлен флаг removeCover в UpdateBookCommand и обработка удаления обложки в UpdateBookUseCase ([ddede16](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ddede16))
- **#36** - ограничено отображение демо-учетных данных только для окружения разработки ([429cfad](https://github.com/WarLikeLaux/yii2-book-catalog/commit/429cfad))

### 🐛 Исправления

- **#33** - исправлена ошибка URL-схемы обложки в CreateBookUseCaseTest ([b2d936d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b2d936d))
- **#33** - исправлено проглатывание исключений в SubscribeUseCase ([eed3cc3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/eed3cc3))
- **#33** - исправлена отсутствующая зависимость в PipelineFactoryTest ([a16b19a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a16b19a))
- **#34** - исправлены e2e-тесты: обновлен ассерт на локализованный заголовок страницы входа 'Вход' ([6cbf0e5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6cbf0e5))
- **#35** - исправлены skip-паттерны rector, fallback покрытия в Makefile, cleanBody regex, слияние .env, guard isSingleLineVarDoc, опечатка в commit.md ([e44e9a2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e44e9a2))
- **#35** - исправлена плюрализация authors_not_found, добавлены переводы ui.username/password/remember_me, исправлены HTTP-методы publish и subscribe в routes.yaml ([679d360](https://github.com/WarLikeLaux/yii2-book-catalog/commit/679d360))
- **#35** - исправлен уязвимый к ReDoS regex в cleanBody путём объединения `\s*\|?\s*` в `[\s|]*` ([7767095](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7767095))
- **#35** - исправлена интерполяция переменных сидера и сравнение булевых PostgreSQL в миграции ([fa1249d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fa1249d))
- **#35** - замена picsum.photos на placehold.jp ([ff1c897](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ff1c897))
- **#36** - исправлена инвалидация кеша для использования года книги из события вместо текущего ([33607f9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/33607f9))
- **#36** - исправлены проблемы доступности (a11y) в views book, report и subscription ([123c805](https://github.com/WarLikeLaux/yii2-book-catalog/commit/123c805))
- **#36** - исправлен extractVerbMap для обработки enum-выражений в VerbFilter actions ([3a1c689](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3a1c689))
- **#36** - исправлен отступ, добавлена проверка длины data.errors и guard версии Node 18+ в resolve-pr-threads.mjs ([f70d3c1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f70d3c1))

### 🛠 Рефакторинг и архитектура

- **#32** - обновлена конфигурация линтеров для поддержки авто-импортов ([a4199d7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a4199d7))
- **#32** - рефакторинг кодовой базы для использования импортированных классов ([e5dc20e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e5dc20e))
- **#32** - обновлены зависимости для устранения уязвимостей безопасности ([39a1b4f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/39a1b4f))
- **#33** - рефакторинг контроллеров для использования **BaseController** и **ViewModelRenderer** ([b70ed2f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b70ed2f))
- **#33** - обновлена конфигурация PHPStan для ViewModelRenderer ([2d2e200](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2d2e200))
- **#33** - рефакторинг **WebUseCaseRunner** в **WebOperationRunner** и добавлен runStep ([295c010](https://github.com/WarLikeLaux/yii2-book-catalog/commit/295c010))
- **#33** - обновлены command handlers для использования WebOperationRunner и runStep ([ed2c867](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ed2c867))
- **#33** - рефакторинг форм на constructor DI: **AuthorForm** ([b3ea5c6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b3ea5c6)), **BookForm** ([4167153](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4167153)), **SubscriptionForm** ([be9d2e5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/be9d2e5))
- **#33** - удален **RepositoryAwareForm** как более не используемый ([84a8a3a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/84a8a3a))
- **#33** - локализованы все представления и стандартизированы PHPDoc блоки ([a5e1f11](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a5e1f11))
- **#33** - рефакторинг persistence-слоя и ужесточены архитектурные правила ([710ae8b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/710ae8b))
- **#33** - рефакторинг **BookItemViewFactory** для улучшенной обработки данных ([87fbafc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/87fbafc))
- **#33** - рефакторинг презентационного слоя для использования специализированных view model factories и DTO ([e8d11c7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e8d11c7))
- **#33** - финализировано покрытие view models для всех оставшихся controller actions ([90ec9d0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/90ec9d0))
- **#33** - рефакторинг **BookController** — логика action реализована через early returns ([ac1e080](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ac1e080))
- **#33** - рефакторинг **SiteController** login action через early returns ([b5b4bfe](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b5b4bfe))
- **#33** - миграция **SubscriptionController** на BaseController ([42f137b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/42f137b))
- **#33** - модифицирован BaseController для поддержки рендеринга error view ([6687fe3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6687fe3))
- **#33** - стандартизированы имена actions в контроллерах через **ActionName enum** ([21d6166](https://github.com/WarLikeLaux/yii2-book-catalog/commit/21d6166))
- **#33** - рефакторинг обработки обложек в book handlers ([a763d14](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a763d14))
- **#33** - переименован метод разрешения URL обложки в BookReadDto ([6ab7c62](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6ab7c62))
- **#33** - рефакторинг детекции MIME-типов и обработки файлов ([2ca499c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2ca499c))
- **#33** - рефакторинг валидации ISBN ([fb8b860](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fb8b860))
- **#33** - рефакторинг web operation runner и pipeline ([9cc91ef](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9cc91ef))
- **#33** - уточнена обработка ошибок use cases ([99b0456](https://github.com/WarLikeLaux/yii2-book-catalog/commit/99b0456))
- **#33** - извлечён **ErrorMappingTrait** для унифицированной обработки ошибок в handlers ([7c37651](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7c37651))
- **#33** - бизнес-валидация перенесена из BookForm в UseCases, удалены зависимости Application ([ffdbb14](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ffdbb14))
- **#33** - удалены защитные проверки instanceof после AutoMapper в BookItemViewFactory ([14e4a2f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/14e4a2f))
- **#33** - удалены защитные проверки instanceof в factories, добавлены тесты ErrorMappingTrait ([38c99e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/38c99e8))
- **#33** - рефакторинг репозиториев: возврат int и выброс исключений ([2cc04e6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2cc04e6))
- **#33** - извлечён **BookDtoUrlResolver** для дедупликации логики представления ([b9a95a9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b9a95a9))
- **#33** - рефакторинг BookDtoUrlResolver для поддержки placeholder URL ([87c85e3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/87c85e3))
- **#33** - переименование pageSize → limit в pagination requests ([57f7d1f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/57f7d1f))
- **#33** - рефакторинг AuthorSearchHandler для удаления зависимости от AutoMapper ([fac486b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fac486b))
- **#33** - рефакторинг PaginationRequest для принятия параметра defaultLimit ([c350a67](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c350a67))
- **#33** - извлечен **BookViewModelMapper** из handlers ([8e529f4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8e529f4))
- **#33** - рефакторинг AuthorListViewFactory для принятия Request ([f3ca6bb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f3ca6bb))
- **#33** - упрощена обработка пагинации BookController ([a9024d3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a9024d3))
- **#33** - замена UseCaseHandlerTrait на inline error handling ([f6e31e0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f6e31e0))
- **#33** - упрощена логика рендеринга ViewModelRenderer ([4de9f29](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4de9f29))
- **#33** - обновлена валидация года BookForm для допуска +5 лет ([01f379d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/01f379d))
- **#33** - переименованы Read-side handlers в конвенцию **\*ViewFactory** ([ef0f989](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ef0f989))
- **#33** - инкапсулирована логика пагинации в BookSearchHandler ([97e81cc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/97e81cc))
- **#33** - рефакторинг презентационного слоя: handlers и factories ([21188d1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/21188d1))
- **#33** - рефакторинг subscribe usecase и тестов для декларативной обработки ошибок ([28e0604](https://github.com/WarLikeLaux/yii2-book-catalog/commit/28e0604))
- **#33** - рефакторинг book usecases и тестов для декларативной обработки ошибок ([4a86f6d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4a86f6d))
- **#33** - рефакторинг author usecases и тестов для декларативной обработки ошибок ([64080a8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/64080a8))
- **#33** - рефакторинг YiiAuthAdapter::login() для выброса OperationFailedException ([245a963](https://github.com/WarLikeLaux/yii2-book-catalog/commit/245a963))
- **#33** - рефакторинг SiteController::actionLogin() для использования try-catch с addFormError() ([8f5808b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8f5808b))
- **#33** - удалены избыточные pagination adapters, инкапсулировано создание PaginationRequest в ViewFactories ([5db4c45](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5db4c45))
- **#33** - переименование pageSize → limit в DTOs ([25fc64b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/25fc64b)), application interfaces ([9be4193](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9be4193)), infrastructure query services ([4859e1a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4859e1a))
- **#33** - рефакторинг **AuthorRepository** для использования ActiveRecordHydrator и Author::reconstitute() ([27e93e6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/27e93e6))
- **#33** - перенесена **AuthorIdCollection** в application/common/values ([50c6f17](https://github.com/WarLikeLaux/yii2-book-catalog/commit/50c6f17))
- **#33** - рефакторинг маппинга Book для удаления зависимости Infrastructure → Application ([5fdfb43](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5fdfb43))
- **#34** - рефакторинг **BookCommandHandler**: удален runStep для command mapping, прямой вызов mapper ([654b115](https://github.com/WarLikeLaux/yii2-book-catalog/commit/654b115))
- **#34** - рефакторинг **AuthorCommandHandler**: удален runStep для command mapping, прямой вызов mapper ([237325e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/237325e))
- **#34** - рефакторинг **BookItemViewFactory**: инжектирован BookViewModelMapper, удален private mapToViewModel ([8ced806](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8ced806))
- **#34** - рефакторинг контроллеров: извлечены renderCreateForm, renderUpdateForm, renderLoginForm (DRY) ([eb2d059](https://github.com/WarLikeLaux/yii2-book-catalog/commit/eb2d059))
- **#34** - извлечен **syncManyToMany** в BaseActiveRecordRepository из BookRepository ([1f583b7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1f583b7))
- **#34** - рефакторинг **SubscribeUseCase**: удален try-catch, исключения обрабатываются middleware ([a8a19f6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a8a19f6))
- **#34** - рефакторинг **DeleteAuthorUseCase**: удален try-catch, исключения обрабатываются middleware ([55cd61a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/55cd61a))
- **#34** - рефакторинг **Subscription**: приватный конструктор, добавлен reconstitute() ([ec86aec](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ec86aec))
- **#34** - рефакторинг **SubscriptionRepository**: инжектирован ActiveRecordHydrator ([ee36d60](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ee36d60))
- **#34** - добавлен readonly к stateless mappers и factories ([e4c7998](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e4c7998))
- **#34** - удалены комментарии из WebOperationRunner и MultilineViewVarAnnotationRector ([8796278](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8796278))
- **#34** - стандартизованы представления: замена hardcoded strings на Yii::t(), alert() на Bootstrap Toast, http:// на динамическую схему ([89af363](https://github.com/WarLikeLaux/yii2-book-catalog/commit/89af363))
- **#34** - рефакторинг inline-стилей в CSS-классы: book-cover-container, book-cover-img, text-hint, col-narrow, col-books-count, footer-desc ([4343a24](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4343a24))
- **#34** - упрощен common.php: замена 17 ручных register() вызовов на DomainErrorMappingRegistry::fromEnum() ([2ac4021](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2ac4021))
- **#34** - стандартизованы stateless infrastructure классы: добавлен модификатор readonly ([13bcd33](https://github.com/WarLikeLaux/yii2-book-catalog/commit/13bcd33))
- **#34** - стандартизован PaginationRequest: добавлен модификатор final ([b7777f6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b7777f6))
- **#34** - удален мертвый DomainErrorMappingProviderInterface ([89da214](https://github.com/WarLikeLaux/yii2-book-catalog/commit/89da214))
- **#34** - удален дубликат infrastructure StorageConfig, унифицирован c application StorageConfig ([00a3160](https://github.com/WarLikeLaux/yii2-book-catalog/commit/00a3160))
- **#34** - добавлен native typehint к DatabaseErrorCode::isDuplicate(), удалены мертвые FOREIGN_KEY константы ([1410029](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1410029))
- **#34** - стандартизовано error.php: замена hardcoded English на Yii::t() ([fd044d0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fd044d0))
- **#34** - стандартизовано login.php: замена hardcoded English на Yii::t(), удалена мертвая ссылка на app\models\User ([a2f8817](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a2f8817))
- **#35** - замена is_numeric на ctype_digit в AuthorIdCollection, RuntimeException на OperationFailedException в CreateBookUseCase, добавлены array_key_exists guard и resolveExceptionClass в DomainErrorMappingRegistry ([008ed64](https://github.com/WarLikeLaux/yii2-book-catalog/commit/008ed64))
- **#35** - добавлены null-checks в breadcrumbs, исправлены PHPDoc типы, htmx:configRequest, dataType в AJAX, showAllErrors опция, оптимизирован BookItemViewFactory до single query ([a597357](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a597357))
- **#36** - рефакторинг сущности Book: замена publish() на transitionTo(), добавлен BookStatusChangedEvent, удален BookPublishedEvent ([ae1ba3a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ae1ba3a))
- **#35** - рефакторинг ErrorSummaryWidget: извлечена опция showAllErrors из widget options ([b023ac9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b023ac9))
- **#35** - обновлены views и translations: параметризована подсказка login, добавлен Html::decode для footer, исправлен атрибут authorNames ([f2e8bcb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f2e8bcb))
- **#35** - обновлен SeedController: использование BookStatus enum вместо строковых литералов ([c6351a0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c6351a0))
- **#35** - рефакторинг PR скриптов: улучшен парсинг .env, обработка ошибок, добавлена проверка версии Node.js ([b727c64](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b727c64))
- **#36** - стандартизованы JS-конвенции: node: imports, Number.parseInt, replaceAll, optional chaining ([8d1f1de](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8d1f1de))
- **#36** - извлечены дублированные строковые литералы в константы в тестовых файлах ([a0d4ac8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a0d4ac8))
- **#35** - удалён избыточный self-transition guard в BookStatus::canTransitionTo для уничтожения escaped мутанта ([52629b9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/52629b9))
- **#35** - удалены лишние inline-комментарии из AutoDocService, fetch-pr-comments и resolve-pr-threads ([69fc946](https://github.com/WarLikeLaux/yii2-book-catalog/commit/69fc946))

### 🧪 Тестирование

- **#33** - обновлены unit-тесты для WebOperationRunner и command handlers ([b828fcd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b828fcd))
- **#33** - обновлены интеграционные тесты для использования локализованных строк ([e99fbae](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e99fbae))
- **#33** - обновлены и добавлены unit и integration тесты для рефакторинга презентационного слоя ([4a68ce2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4a68ce2))
- **#33** - добавлены unit-тесты для BuggregatorLogTarget ([156e7c9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/156e7c9))
- **#33** - добавлены unit-тесты для InspectorSpan ([c64119c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c64119c))
- **#33** - добавлены unit-тесты для InspectorTracer ([f3d35f4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f3d35f4))
- **#33** - добавлены unit-тесты для SmsPilotSender ([36d9539](https://github.com/WarLikeLaux/yii2-book-catalog/commit/36d9539))
- **#33** - улучшено покрытие инфраструктурных адаптеров ([073a77e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/073a77e))
- **#33** - добавлены unit-тесты для валидации невалидных значений в AuthorIdCollection ([94768f2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/94768f2))
- **#33** - добавлены тесты для edge cases валидации в формах ([50da3c1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/50da3c1))
- **#33** - обновлены тесты для рефакторинга handlers и mappers ([eda0639](https://github.com/WarLikeLaux/yii2-book-catalog/commit/eda0639))
- **#33** - обновлены тесты author usecases для использования Author::reconstitute() ([762212e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/762212e))
- **#33** - обновлен YiiAuthAdapterTest для ожидания OperationFailedException ([5eabfe9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5eabfe9))
- **#33** - обновлены unit-тесты для переименованных ViewFactory классов ([20f08a1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/20f08a1))
- **#33** - обновлен интеграционный тест для SubscriptionViewFactory ([db9ebd5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/db9ebd5))
- **#34** - добавлены тесты для EntityNotFoundException для уничтожения мутанта IncrementInteger ([4b5b450](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4b5b450))
- **#34** - добавлены тесты для AlreadyExistsException, BusinessRuleException, DomainErrorMappingRegistry::fromEnum() ([8a5065b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8a5065b))
- **#34** - добавлены тесты для DomainErrorCode атрибутов и ErrorMapping ([a244ac6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a244ac6))
- **#34** - добавлен DomainExceptionTranslationMiddlewareTest: 100% покрытие, уничтожен мутант Identical ([3d576db](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3d576db))
- **#35** - обновлены тесты: OperationFailedException в CreateBookUseCaseTest, single query в BookItemViewFactoryTest, исправлен storedCover, аргументы willReturnMap, рефакторинг TracerBootstrapTest, покрытие DomainErrorMappingRegistryTest resolveExceptionClass, обновлен ассерт BookValidationCest ([5cc6ef2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5cc6ef2))
- **#35** - улучшены тесты: удалены комментарии, оптимизирована настройка моков, исправлена обработка temp файлов и значений по умолчанию ([642ce6c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/642ce6c))
- **#35** - добавлены unit-тесты для виджетов BookStatusBadge и BookStatusActions ([4ebf547](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4ebf547))
- **#36** - добавлены тесты BookStatus и BookStatusChangedEvent, обновлены тесты domain entity и specification ([9d043b7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9d043b7))
- **#36** - добавлен ChangeBookStatusUseCaseTest, обновлен BookTestHelper и application тесты для BookStatus ([90f2e09](https://github.com/WarLikeLaux/yii2-book-catalog/commit/90f2e09))
- **#36** - обновлены infrastructure тесты для BookStatus, nullable job mapping и status-aware listeners ([76353d3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/76353d3))
- **#36** - обновлены presentation и integration тесты для BookStatus workflow ([d691044](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d691044))
- **#36** - добавлены try/finally для очистки контейнера и недостающий ассерт BookStatusChangedEvent ([e1fc77b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e1fc77b))
- **#36** - добавлен тест ветки removeCover в UpdateBookUseCaseTest для восстановления 100% покрытия ([50a7d8c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/50a7d8c))

### ⚙️ Инфраструктура

- **#33** - обновлены настройки покрытия Makefile и dev tools ([f17d3d3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f17d3d3))
- **#33** - обновлены тесты и применены мелкие исправления для согласованности ([bb98809](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bb98809))
- **#33** - обновлен тип исключения в тесте subscribe use case ([39de29a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/39de29a))
- **#33** - обновлены оставшиеся тесты для изменений в репозиториях ([c65e7e7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c65e7e7))
- **#33** - оптимизирован subscriptions controller и исправлено логирование ([9953f42](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9953f42))
- **#33** - рефакторинг целей Makefile для улучшения CI и Dev workflow ([2e9e62a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e9e62a))
- **#33** - защищено выполнение git в fetch-pr-comments.mjs ([60c261a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/60c261a))
- **#33** - стандартизована цель analyze в Makefile ([f2664d6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f2664d6))
- **#33** - обновлена конфигурация проекта и документация ([fd8b734](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fd8b734))
- **#33** - обновлены сообщения локализации и настроен i18n ([9aca8d6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9aca8d6))
- **#33** - введено кастомное rector правило для multiline view annotations и отформатированы templates ([3a9456c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3a9456c))
- **#33** - обновлены локализованные сообщения об ошибках ([f5e9cbf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f5e9cbf))
- **#33** - обновлены контроллеры и views для использования унифицированной обработки ошибок ([cf2fef4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cf2fef4))
- **#33** - добавлены переводы и error mapping для исключения authors_not_found ([03bd837](https://github.com/WarLikeLaux/yii2-book-catalog/commit/03bd837))
- **#34** - обновлены описания help текста в Makefile ([015976b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/015976b))
- **#34** - добавлен @codeCoverageIgnore к YiiAuthAdapter::login untestable branch ([4d54d94](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4d54d94))
- **#34** - обновлен SubscriptionController::actionForm: добавлено HTMX detection, fallback на full view ([a2f7e32](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a2f7e32))
- **#34** - добавлены ключи переводов i18n для error, login, api, index, report views ([0a4ea1c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0a4ea1c))
- **#35** - рефакторинг list-comments в линтер комментариев с флагами --notes/--ignores/--all, добавлен в make dev ([ac9b5d5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ac9b5d5))
- **#36** - исправлена перезапись REVIEW.md при наличии существующих записей и нормализованы разделители ([bad4281](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bad4281))
- **#36** - обновлен readme workflow: замена make test на make test-full ([7565751](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7565751))
- **#36** - синхронизирована структура проекта и обновлены метрики в README.md и ARCHITECTURE.md ([db30e2a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/db30e2a))
- **#36** - синхронизирован docs/structure.yaml с файловой системой: удалён фантомный logging, добавлены отсутствующие поддиректории модулей ([38f5fac](https://github.com/WarLikeLaux/yii2-book-catalog/commit/38f5fac))

### 📝 Документация

- **#33** - обновлена документация для отражения constructor DI в формах ([4f5bb0a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4f5bb0a))
- **#33** - обновлен application layer книг и persistence ([9352ea5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9352ea5))
- **#33** - обновлена документация и UI views ([2385ec3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2385ec3))
- **#33** - обновлен скрипт fetch-pr-comments.mjs для улучшенной обработки путей и промптов ([2c93948](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2c93948))
- **#33** - обновлена презентация книг и command handling ([33c835f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/33c835f))
- **#33** - обновлены инструкции роли и стиля в CLAUDE.md ([6f1bf6d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6f1bf6d))
- **#33** - синхронизированы автогенерированные docs ([6be6578](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6be6578))
- **#33** - обновлен контроллеры для использования ViewFactory классов ([b69ce9d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b69ce9d))
- **#33** - обновлены внутренние ссылки ViewFactory классов ([c53ff3f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c53ff3f))
- **#33** - обновлены workflow и contract docs ([d1c571b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d1c571b))
- **#33** - обновлен workflow и документация ([e1cc4d3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e1cc4d3))
- **#33** - обновлена конституция и правила AI ([a898e73](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a898e73))
- **#33** - исправлены ошибки статического анализа после обновления зависимостей ([daf9d40](https://github.com/WarLikeLaux/yii2-book-catalog/commit/daf9d40))
- **#35** - добавлен REVIEW.md со всеми 31 элементами обратной связи PR ([42ba37e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/42ba37e))
- **#35** - обновлена документация: удалена колонка is_published из db.yaml, обновлен lastmod sitemap ([30e1c66](https://github.com/WarLikeLaux/yii2-book-catalog/commit/30e1c66))
- **#35** - обновлена документация ([e9ebad0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e9ebad0))
- **#35** - переименован `BookCrudCest` в `BookUpdateDeleteCest` в интеграционных тестах ([2b229ae](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2b229ae))
- **#35** - добавлен guard создания директории перед записью REVIEW.md ([688a451](https://github.com/WarLikeLaux/yii2-book-catalog/commit/688a451))
- **#36** - добавлены миграции status, обновлен container config, seed, arkitect rules и auto-generated docs ([2721c7a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2721c7a))
- **#36** - добавлена логика слияния для сохранения существующих записей REVIEW.md при повторном fetch ([f9b7564](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f9b7564))
- **#36** - обновлена документация (ARCHITECTURE, COMPARISON, DECISIONS, README) ([b652585](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b652585))
- **#36** - исправлены метрики README.md по результатам bin/validate-docs ([1c01659](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1c01659))
- **#36** - обновлен help текст Makefile и таблица фич README для согласованности ([0b1f30f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0b1f30f))

</details>

## [0.18.0] - 2026-01-10 - "Декларативный маппинг, CAS-хранилище и укрепление инфраструктуры"

> Грандиозное обновление, вобравшее в себя более 200 коммитов: внедрен AutoMapper для декларативного маппинга DTO и механизм ActiveRecordHydrator для автоматизации сохранения сущностей. Реализована система хранения CAS (Content-Addressable Storage), сервис MimeTypeDetector и надежные механизмы идемпотентности. Архитектура ядра значительно укреплена за счет строгого контроля PHPStan, внедрения UseCaseHandlerTrait для унифицированной обработки ошибок и глобальной стандартизации стиля (Prettier, PHPCS). Проведен масштабный рефакторинг всех слоев приложения и инструментов тестирования, обеспечивший абсолютную стабильность CI/CD.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#32** - внедрена библиотека **AutoMapper** и атрибуты `MapTo` для декларативного преобразования объектов ([c1e465e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c1e465e), [21be436](https://github.com/WarLikeLaux/yii2-book-catalog/commit/21be436), [639f3ff](https://github.com/WarLikeLaux/yii2-book-catalog/commit/639f3ff))
- **#32** - добавлена поддержка расширений файлов, времени модификации и валидации путей в слое хранения ([23ef66f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/23ef66f), [52f11da](https://github.com/WarLikeLaux/yii2-book-catalog/commit/52f11da), [c904f8e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c904f8e))
- **#32** - реализовано автоматическое удаление лишних пробелов (trimming) в доменных сущностях ([71ff241](https://github.com/WarLikeLaux/yii2-book-catalog/commit/71ff241))
- **#32** - реализован **ActiveRecordHydrator** для автоматического сохранения состояния сущностей ([b01028a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b01028a), [04a9626](https://github.com/WarLikeLaux/yii2-book-catalog/commit/04a9626))
- **#32** - реализована **контентно-адресуемая система хранения** (Content-Addressable Storage) для оптимизации работы с файлами ([a9747e6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a9747e6), [72940d0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/72940d0), [29673c9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/29673c9), [bdee41b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bdee41b), [23ef66f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/23ef66f))
- **#32** - доработана контентно-адресуемая система хранения: стандартизированы исключения, добавлена поддержка расширений и первичных ключей ([a9747e6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a9747e6), [5329b4c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5329b4c), [a41f8cc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a41f8cc))
- **#32** - внедрена поддержка слушателей маппинга для Yii2 ActiveRecord ([9fe70f4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9fe70f4))
- **#32** - реализовано асинхронное хранилище **идемпотентности** для очередей ([a9b2ec2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a9b2ec2), [b8ea1f0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b8ea1f0))
- **#32** - внедрен интерфейс **IdentifiableEntityInterface** для стандартизации работы с идентификаторами ([9231e55](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9231e55))
- **#32** - добавлена поддержка расширений файлов, времени модификации и валидации путей в слое хранения ([23ef66f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/23ef66f), [52f11da](https://github.com/WarLikeLaux/yii2-book-catalog/commit/52f11da), [c904f8e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c904f8e))
- **#32** - добавлена консольная команда `StorageController` для обслуживания хранилища и очистки от сиротских файлов ([19687e1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/19687e1), [6a6e7e5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6a6e7e5))
- **#32** - добавлены новые доменные коды ошибок и обновлены переводы ([150f1fb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/150f1fb))
- **#32** - реализовано HMAC хеширование телефонов ([16a48d8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/16a48d8))
- **#32** - добавлен индекс для URL обложек и увеличена длина ключа идемпотентности ([2f891d8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2f891d8))
- **#32** - реализовано получение ключей обложек и декоратор трассировки ([e5238a3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e5238a3))
- **#32** - реализовано маскирование PII в уведомлениях подписчиков ([832c3a0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/832c3a0))
- **#32** - добавлена миграция для переименования индекса ISBN в книгах ([f585c9a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f585c9a))
- **#32** - добавлен сервис **MimeTypeDetector** и соответствующие unit-тесты ([aa612f0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/aa612f0))
- **#32** - добавлен интеграционный тест на реверсивность миграций **MigrationReversibilityTest** ([9bbbee0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9bbbee0))
- **#32** - добавлен тест на валидацию директорий в **FileContent** ([1ec620c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1ec620c))
- **#32** - реализовано получение ключей обложек и декоратор трассировки ([e5238a3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e5238a3))

### 🛠 Рефакторинг и архитектура

- **#32** - масштабный рефакторинг презентационного слоя: замена ручных мапперов на **AutoMapper** ([885f1a5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/885f1a5), [429b3e1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/429b3e1), [0f6734a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0f6734a))
- **#32** - внедрен **BaseQueryService** и **BaseActiveRecordRepository** для унификации логики доступа к данным ([ba83beb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ba83beb), [b960b0e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b960b0e), [8544a8d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8544a8d), [2eb9cd1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2eb9cd1), [6c41af6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6c41af6), [b9d7f0d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b9d7f0d), [4c061ca](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4c061ca), [11c2c3a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/11c2c3a))
- **#32** - исправлены побочные эффекты сортировки массивов в репозитории книг ([e290eb2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e290eb2))
- **#32** - внедрен трейт **UseCaseHandlerTrait** для унифицированной обработки результатов и маппинга ошибок ([533b5ea](https://github.com/WarLikeLaux/yii2-book-catalog/commit/533b5ea), [68c152a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/68c152a), [e9b153d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e9b153d), [60593e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/60593e8))
- **#32** - рефакторинг инфраструктурных компонентов и сервисов запросов ([cb6a568](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cb6a568))
- **#32** - реализована эффективная очистка WeakMap в репозиториях для предотвращения утечек памяти ([46cac51](https://github.com/WarLikeLaux/yii2-book-catalog/commit/46cac51))
- **#32** - оптимизировано извлечение идентификаторов сущностей в базовом репозитории ([485567c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/485567c))
- **#32** - репозитории переведены на использование **ActiveRecordHydrator**, **Identity Map** и строгую типизацию ([04a9626](https://github.com/WarLikeLaux/yii2-book-catalog/commit/04a9626), [e65855d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e65855d), [b8cf289](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b8cf289), [f74adf2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f74adf2))
- **#32** - внедрен **UploadedFileAdapter** для чистой обработки загрузок вне контроллеров ([0125b98](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0125b98), [ed66287](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ed66287))
- **#32** - рефакторинг поиска книг: переход на интерфейс Query Service и унификация хендлеров ([9c481f6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9c481f6), [682527b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/682527b), [91928ed](https://github.com/WarLikeLaux/yii2-book-catalog/commit/91928ed), [8118ac5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8118ac5))
- **#32** - добавлена поддержка пустых результатов (empty factory) в QueryResult и соответствующие тесты ([0fdd749](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0fdd749), [2907ba3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2907ba3))
- **#32** - проведена изоляция инфраструктуры и презентации через новые интерфейсы и декораторы ([f245bb0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f245bb0), [e144cab](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e144cab), [af07bf9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/af07bf9))
- **#32** - рефакторинг **FileContent** и системы хранения: переход на доменные исключения и удаление легаси middleware ([43d076f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/43d076f), [33ac91c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/33ac91c), [bdee41b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bdee41b), [2d94980](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2d94980))
- **#32** - централизована валидация переменных окружения через **Dotenv** ([84867db](https://github.com/WarLikeLaux/yii2-book-catalog/commit/84867db), [a91a105](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a91a105))
- **#32** - рефакторинг извлечения ключей обложек ([6c8e9e1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6c8e9e1))
- **#32** - обновлена логика обработчика уведомлений и тесты ([d74e668](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d74e668))
- **#32** - улучшена обработка ошибок и логирование в обработчиках команд ([74ccf01](https://github.com/WarLikeLaux/yii2-book-catalog/commit/74ccf01))
- **#32** - рефакторинг сервиса хранения и обновлен Value Object FileKey ([e3f69de](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e3f69de))
- **#32** - рефакторинг фабрик данных представления со строгой проверкой типов ([e98c686](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e98c686))
- **#32** - рефакторинг BaseActiveRecordRepository и добавлены unit-тесты ([fa7f086](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fa7f086))
- **#32** - мелкие исправления и улучшения в домене, инфраструктуре и тестах ([71b9ef9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/71b9ef9))
- **#32** - рефакторинг презентационных хендлеров и фабрик данных для **AutoMapper** ([bf8f335](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bf8f335))
- **#32** - оптимизирована таблица очереди путем добавления композитного индекса ([8138648](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8138648))
- **#32** - расширена максимальная длина ключа идемпотентности и обновлено его использование в репозиториях ([a204d9d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a204d9d))
- **#32** - рефакторинг интерфейса **IdentifiableEntityInterface** и стандартизация доступа к идентификаторам сущностей ([26a4cab](https://github.com/WarLikeLaux/yii2-book-catalog/commit/26a4cab))
- **#32** - рефакторинг **AutoDocService**: улучшен парсинг типов из PHPDoc и сигнатур методов ([eaffc90](https://github.com/WarLikeLaux/yii2-book-catalog/commit/eaffc90))
- **#32** - рефакторинг **FileContent** и улучшена детекция MIME-типов ([12ee6ce](https://github.com/WarLikeLaux/yii2-book-catalog/commit/12ee6ce))
- **#32** - рефакторинг репозиториев и сервисов уведомлений для повышения надежности ([1bab08f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1bab08f))
- **#32** - рефакторинг фабрик данных представления и виджетов со строгой типизацией ([e0fac02](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e0fac02))
- **#32** - рефакторинг **NativeMimeTypeDetector** для использования обертки над `finfo` ([0dc9001](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0dc9001))
- **#32** - рефакторинг детекции MIME-типов и перенос тестов ([3a35250](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3a35250))
- **#32** - рефакторинг персистентности репозиториев и внедрены константы IdempotencyKey ([b18cda4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b18cda4))
- **#32** - рефакторинг логики персистентности инфраструктуры и моделей ([d114a21](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d114a21))
- **#32** - оптимизирован поиск сущности при удалении из идентичности репозитория ([01794c9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/01794c9))
- **#32** - упрощено удаление сущности из карты идентичностей репозитория ([9dc828b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9dc828b))
- **#32** - исправлена логика сохранения для корректной синхронизации авторов новых книг ([cf7fded](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cf7fded))
- **#32** - рефакторинг геттеров ActiveRecord в книгах для предотвращения неявного приведения к null ([f1edb24](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f1edb24))
- **#32** - консолидирован метод `findById` в `BookQueryService` ([af6b466](https://github.com/WarLikeLaux/yii2-book-catalog/commit/af6b466))
- **#32** - консолидирован метод `findById` в `BookQueryService` ([af6b466](https://github.com/WarLikeLaux/yii2-book-catalog/commit/af6b466))
- **#32** - рефакторинг `AutoDocService` для лучшей нормализации типов ([0df82fb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0df82fb))
- **#32** - рефакторинг `IdempotencyFilter` для более эффективной обработки повторных попыток ([956c817](https://github.com/WarLikeLaux/yii2-book-catalog/commit/956c817))
- **#32** - рефакторинг слоя персистентности для улучшения обработки ошибок и жадной загрузки ([6293969](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6293969))
- **#32** - рефакторинг фильтров и адаптеров для улучшения конфигурации и соответствия стилю ([2ea39d0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2ea39d0))
- **#32** - исправлена типизация и переименованы переменные в слое инфраструктуры ([3d169bf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3d169bf))

### 🧪 Тестирование

- **#32** - внедрен **RemovesDirectoriesTrait** для декларативного удаления директорий в тестах ([57b1052](https://github.com/WarLikeLaux/yii2-book-catalog/commit/57b1052))
- **#32** - расширено покрытие тестами для новых инфраструктурных компонентов, мапперов и базовых сервисов ([5e72a89](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5e72a89), [581f8af](https://github.com/WarLikeLaux/yii2-book-catalog/commit/581f8af), [c3434b8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c3434b8), [12fc530](https://github.com/WarLikeLaux/yii2-book-catalog/commit/12fc530), [52dd179](https://github.com/WarLikeLaux/yii2-book-catalog/commit/52dd179))
- **#32** - обновлена конфигурация инструментов тестирования и добавлены вспомогательные ресурсы ([10d5a30](https://github.com/WarLikeLaux/yii2-book-catalog/commit/10d5a30))
- **#32** - стабилизированы тесты идемпотентности, очереди уведомлений и поиска при валидационных ошибках ([f9ca737](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f9ca737), [2e4bb43](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e4bb43), [c2705ba](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c2705ba), [4d868e1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4d868e1))
- **#32** - обновлен мок хранилища с обложками в тестах хендлеров ([86f72d5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/86f72d5))
- **#32** - удалены неиспользуемые DTO (AuthorSearchResponse) и обновлены тесты IdentifiableEntity ([b7e96e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b7e96e8), [4c568c3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4c568c3))
- **#32** - улучшена надежность и читаемость unit-тестов: удалены неиспользуемые хелперы и добавлены строгие ассерты ([3cd12e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3cd12e8), [342d7fa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/342d7fa), [e2dcc83](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e2dcc83))
- **#32** - обновлены тесты сервиса запросов ([b6de553](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b6de553))
- **#32** - обновлены зависимости тестов обработчика уведомлений ([7091a0c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7091a0c))
- **#32** - упрощен тест обработчика команд книг ([54679e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/54679e8))
- **#32** - рефакторинг помощника тестов репозитория ([ff625e3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ff625e3))
- **#32** - улучшена верификация моков и работа с ресурсами в BookCommandHandlerTest ([e233627](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e233627))
- **#32** - рефакторинг генерации ISBN и добавлено описание книги в ReportQueryServiceTest ([d60c70e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d60c70e))
- **#32** - рефакторинг трейтов поддержки тестов и обработки исключений ([2deec48](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2deec48))
- **#32** - улучшена реализация инфраструктурных запросов и расширено покрытие тестами ([53f79fc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/53f79fc))
- **#32** - обновлен Value Object **FileContent** и добавлены соответствующие unit-тесты ([2039ff5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2039ff5))
- **#32** - рефакторинг тестов сервисов запросов для устранения зависимости от порядка запуска ([a29e903](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a29e903))
- **#32** - добавлены unit-тесты для адаптера аутентификации и виджета алертов ([e0fac02](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e0fac02))
- **#32** - добавлены unit-тесты для базовых классов конфигурации ([903f911](https://github.com/WarLikeLaux/yii2-book-catalog/commit/903f911))
- **#32** - исправлены тесты **NativeMimeTypeDetector** и улучшены тесты валидации конфигурации ([986d753](https://github.com/WarLikeLaux/yii2-book-catalog/commit/986d753))
- **#32** - улучшена обработка исключений в **UploadedFileAdapter** и укреплены тесты ([499247e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/499247e))
- **#32** - переименован тестовый метод для большей ясности ([8926d8c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8926d8c))
- **#32** - укреплены юнит-тесты и исправлены граничные случаи ([fd727d5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fd727d5))
- **#32** - добавлен тест на ошибку блокировки в фильтре идемпотентности ([1036990](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1036990))
- **#32** - обработаны граничные случаи и игнорирование покрытия в репозиториях ([26bf1e7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/26bf1e7))
- **#32** - укреплены тесты конфигурации хранилища и репозиториев ([c761f52](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c761f52))
- **#32** - исправлены и обновлены тесты базового репозитория ([72b715f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/72b715f))
- **#32** - исправлены и обновлены тесты базового репозитория ([72b715f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/72b715f))

### ⚙️ Инфраструктура

- **#32** - внедрены новые правила **PHPStan** для контроля использования ActiveRecord, изоляции слоев и типизации ([c6333aa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c6333aa), [876e37a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/876e37a), [f1689a2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f1689a2), [9b13db0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9b13db0), [db7738d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/db7738d), [50ba834](https://github.com/WarLikeLaux/yii2-book-catalog/commit/50ba834), [07021d4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/07021d4), [9e96c52](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9e96c52), [b53c3cc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b53c3cc), [6880b8b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6880b8b), [878e384](https://github.com/WarLikeLaux/yii2-book-catalog/commit/878e384))
- **#32** - оптимизирована загрузка конфигураций и стандартизованы выражения `include` ([9baeab0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9baeab0))
- **#32** - упрощен код и удалены избыточные комментарии из доменной логики и сервисов ([979ae2b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/979ae2b), [b2c33c2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b2c33c2))
- **#32** - рефакторинг **Makefile**: перенос логики в изолированные bash-скрипты и оптимизация утилит ([d062726](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d062726), [fd87b22](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fd87b22), [5fcc6e3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5fcc6e3), [737940b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/737940b), [2b366b5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2b366b5))
- **#32** - обновлены скрипты развертывания и инфраструктурные инструменты во время сборки ([d82a28a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d82a28a), [307c0af](https://github.com/WarLikeLaux/yii2-book-catalog/commit/307c0af))
- **#32** - из конфигурации консольного приложения удалены нетехнические комментарии ([95c325b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/95c325b))
- **#32** - доработана автоматическая генерация документации и сервис AutoDoc ([e1fe5b8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e1fe5b8), [bdfaddd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bdfaddd))
- **#32** - оптимизирована работа с частичными индексами в PostgreSQL для обложек книг ([665c851](https://github.com/WarLikeLaux/yii2-book-catalog/commit/665c851))
- **#32** - удалены файлы конфигурации IDE (.vscode) ([774e8ac](https://github.com/WarLikeLaux/yii2-book-catalog/commit/774e8ac))
- **#32** - стандартизованы правила **Rector** и линтинга для новых архитектурных паттернов ([f95cb93](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f95cb93), f95cb93)
- **#32** - добавлена конфигурация Prettier ([3cd2700](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3cd2700))
- **#32** - рефакторинг стиля кода в слое Presentation и конфигурации проекта ([5f58938](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5f58938))
- **#32** - рефакторинг стиля кода в слое Infrastructure ([3eeb4bb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3eeb4bb))
- **#32** - рефакторинг стиля кода в слоях Domain и Application ([afea582](https://github.com/WarLikeLaux/yii2-book-catalog/commit/afea582))
- **#32** - обновлен phpcs.xml.dist с полными правилами стандартов кодирования ([177045d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/177045d))
- **#32** - обновлено правило UseCaseMustBeFinalRule с номером строки и подавлением неиспользуемого параметра ([dc7e7b5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dc7e7b5))
- **#32** - уточнены правила PHPStan и обновлены инструменты обслуживания ([9124aa8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9124aa8))
- **#32** - внедрен помощник env и рефакторинг конфигурации загрузки ([efd3e62](https://github.com/WarLikeLaux/yii2-book-catalog/commit/efd3e62))
- **#32** - обновлена конфигурация структуры проекта ([f4b79c5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f4b79c5))
- **#32** - обновлены скрипты маппинга и валидации проекта ([38a3938](https://github.com/WarLikeLaux/yii2-book-catalog/commit/38a3938))
- **#32** - обновлены конфигурации проекта и рабочий процесс коммитов ([ec99f71](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ec99f71))
- **#32** - усилена валидация конфигурации окружения и безопасность обработки PII-данных ([453ad87](https://github.com/WarLikeLaux/yii2-book-catalog/commit/453ad87))
- **#32** - обновлен **Makefile** и инструменты сборки, добавлен скрипт `bin/test-migration` ([dd79449](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dd79449))
- **#32** - обновлена конфигурация окружения: переход с `bootstrap_env.php` на `env.php` ([d0b6a31](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d0b6a31))
- **#32** - обновлены миграции и добавлена миграция для удаления одиночных индексов очереди ([b2d695f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b2d695f))
- **#32** - проведено объединение и реорганизация миграций базы данных ([5e39ebf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5e39ebf))
- **#32** - обновлен DI-контейнер и бенчмарки адаптеров ([0b9a6b0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0b9a6b0))
- **#32** - обновлены метаданные проекта и переводы ([a99bde4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a99bde4))
- **#32** - обновлены скрипты инфраструктуры и точка входа `test-e2e` ([1e646e2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1e646e2))
- **#32** - обновлен путь резолюции **AutoDocService** и добавлена утилита **env-parser** ([a152f08](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a152f08))
- **#32** - обновлена локализация, очищены миграции и оптимизированы скрипты тестирования ([74b67df](https://github.com/WarLikeLaux/yii2-book-catalog/commit/74b67df))
- **#32** - улучшена безопасность конфигурации и исправлен порядок инициализации фильтров ([5d239ea](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5d239ea))
- **#32** - обновлено количество потоков для мутационного тестирования в ci workflow ([e7c7d39](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e7c7d39))
- **#32** - исправлена начальная версия для оптимистичной блокировки ([cb2caa3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cb2caa3))
- **#32** - улучшены миграции и добавлены русские переводы ([83804b2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/83804b2))
- **#32** - обновлены CI workflow, тестовые скрипты и сервис автодокументации ([97cd11e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/97cd11e))
- **#32** - обновлены переводы и исправлены миграции основных таблиц ([f28ba8d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f28ba8d))
- **#32** - обновлена обработка конфигурации и локализованы сообщения ([4e8a49b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4e8a49b))

### 📝 Документация

- **#32** - добавлен **ADR #13** о принципах построения инфраструктурного ядра ([c92ab94](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c92ab94))
- **#31** - актуализирована документация проекта и реализован лендинг ([f82832a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f82832a), [ba9ca50](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ba9ca50))
- **#32** - доработан **DI-контейнер** и конфигурация репозиториев ([6dd0e8a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6dd0e8a))
- **#32** - рефакторинг поиска и бизнес-логики для соответствия новым сервисам ([ad616d2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ad616d2))
- **#32** - добавлена поддержка **Generics** для PagedResult и QueryResult ([33a23e9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/33a23e9))
- **#31** - обновлен **CHANGELOG.md** с исправлением разметки и добавлением пропущенных коммитов ([d1126db](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d1126db), [3a61e4f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3a61e4f))
- **#32** - финальная шлифовка и обновление документации ([534a9ca](https://github.com/WarLikeLaux/yii2-book-catalog/commit/534a9ca))
- **#32** - обновлены рабочие процессы агента ([04f9de3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/04f9de3))
- **#32** - обновлены переводы и автогенерируемая документация ([87ea3a7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/87ea3a7))
- **#32** - обновлена архитектурная документация и добавлено сравнение реализаций ([cbe5731](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cbe5731))
- **#32** - обновлена логика autodoc и синхронизирована документация ([92f1b4d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/92f1b4d))
- **#32** - улучшен сервис **AutoDoc** для более точного парсинга маршрутов контроллеров ([e4fc190](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e4fc190))
- **#32** - обновлен README.md ([e288582](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e288582))
- **#32** - актуализирована автогенерируемая документация ([74b67df](https://github.com/WarLikeLaux/yii2-book-catalog/commit/74b67df))

### 🐛 Исправления

- **#32** - исправлена обработка ошибок целостности данных в **BaseActiveRecordRepository** ([416580b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/416580b))
- **#32** - мелкие исправления в доменных сущностях, правилах анализа и миграциях ([72307a9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/72307a9))
- **#32** - исправлена опечатка ([4eb0fa7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4eb0fa7))
- **#32** - обновлено сообщение об ошибке повреждения файла ([5dd2c60](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5dd2c60))
- **#32** - исправлена логика репозитория и обработка индексов в миграциях ([90c9b13](https://github.com/WarLikeLaux/yii2-book-catalog/commit/90c9b13))

</details>

## [0.17.0] - 2026-01-06 - "Архитектурный сдвиг и бесконечный скролл"

> Масштабный релиз, внедряющий современную инфраструктуру обработки команд (Command Pipeline), переход на типизированные коды ошибок и реализацию бесконечного скролла с использованием HTMX. Значительно усилен контроль качества через архитектурные тесты (PHPArkitect) и глубокий рефакторинг в соответствии с принципом разделения интерфейсов (ISP).

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#31** - консолидирована логика полнотекстового поиска с использованием LIKE в качестве запасного варианта ([0f3b965](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0f3b965))
- **#31** - внедрена инфраструктура pipeline команд и middleware ([d1b5b0a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d1b5b0a))
- **#31** - реализован интерфейс карточек книг с бесконечным скроллом на базе HTMX ([1220bc1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1220bc1))
- **#31** - внедрена структура API v1 и мигрирован BookController ([0cfdbbf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0cfdbbf))
- **#31** - реализован ActiveQuery visitor для спецификаций книг ([76eb8ce](https://github.com/WarLikeLaux/yii2-book-catalog/commit/76eb8ce))
- **#31** - добавлен код ошибки идемпотентности и соответствующие переводы ([7c64e37](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7c64e37))
- **#31** - улучшена BookPublicationPolicy: добавлены требования к обложке и описанию ([abfe953](https://github.com/WarLikeLaux/yii2-book-catalog/commit/abfe953))
- **#31** - добавлен data-type image в ссылки glightbox для улучшения предпросмотра ([ae38e39](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ae38e39))
- **#31** - добавлены вспомогательные методы в общие DTO ([7e3a029](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7e3a029))

### 🛠 Рефакторинг и архитектура

- **#31** - внедрен DomainErrorCode и проведена масштабная типизация всех исключений ([f186b83](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f186b83), [5870870](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5870870), [c6c374b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c6c374b), [abec5ca](https://github.com/WarLikeLaux/yii2-book-catalog/commit/abec5ca))
- **#31** - внедрен паттерн Visitor для спецификаций книг (устранение логики в сущностях) ([613d7dc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/613d7dc))
- **#31** - рефакторинг в соответствии с ISP: выделены BookFinderInterface и BookSearcherInterface ([6a47b82](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6a47b82), [2e5cf00](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e5cf00))
- **#31** - обновлены хендлеры и WebUseCaseRunner для работы через Command Pipeline ([9f7f911](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9f7f911))
- **#31** - рефакторинг команд и usecase для интеграции with new ports ([560107d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/560107d))
- **#31** - централизована конфигурация Buggregator и улучшено логирование исключений ([94aa83e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/94aa83e))
- **#31** - рефакторинг поискового хендлера для поддержки бесконечного скролла HTMX ([4838af6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4838af6))
- **#31** - рефакторинг аутентификации: переход на AuthServiceInterface и AuthViewDataFactory ([fa87ca5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fa87ca5))
- **#31** - оптимизирован маппинг событий через EventJobMappingRegistry и рефлексию ([bac904a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bac904a), [25bfdf3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/25bfdf3))
- **#31** - исправлены накопленные архитектурные нарушения ([0afa7f2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0afa7f2))
- **#31** - рефакторинг BookYear для использования безопасной валидации через int ([2cea3a8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2cea3a8))
- **#31** - обновлено использование BookYear в приложении и инфраструктуре ([4a48598](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4a48598))
- **#31** - обновлен services.php для регистрации новых интерфейсов ([9bed5a7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9bed5a7))
- **#31** - обновлен FileUrlResolver для использования внутреннего метода разрешения путей ([1df48e7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1df48e7))

### 🧪 Тестирование

- **#31** - добавлена проверка результата выполнения `UpdateAuthorUseCase` для устранения выжившего мутанта ([43a82d4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/43a82d4))
- **#31** - обновлен IdempotencyMiddlewareTest с негативными сценариями и строгими ожиданиями ([49be0a9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/49be0a9))
- **#31** - добавлены тесты для pipeline и middleware, актуализированы тесты usecase ([57f98a8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/57f98a8))
- **#31** - улучшена надежность E2E тестов и актуализированы селекторы ([f75218b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f75218b))
- **#31** - обновлены unit-тесты под новую типизированную структуру исключений ([0eb04a1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0eb04a1), [ea10440](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ea10440))
- **#31** - усилены проверки в DeleteAuthorUseCaseTest и SubscribeUseCaseTest ([a2f7a82](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a2f7a82), [bbebed3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bbebed3))
- **#31** - обновлены тесты для паттерна Visitor и спецификаций ([e9fbdcf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e9fbdcf))
- **#31** - общее исправление и стабилизация тестового набора ([2b13628](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2b13628))

### ⚙️ Инфраструктура

- **#31** - интегрирован PHPArkitect в CI для автоматического контроля архитектуры ([e41e491](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e41e491), [5029387](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5029387))
- **#31** - добавлена инфраструктура и ассеты для поддержки HTMX ([07c1545](https://github.com/WarLikeLaux/yii2-book-catalog/commit/07c1545), [8be8e55](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8be8e55))
- **#31** - актуализирован CI и стандарты качества кода ([844a13c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/844a13c))
- **#31** - рефакторинг конфигурации GrumPHP и Rector ([adf2f63](https://github.com/WarLikeLaux/yii2-book-catalog/commit/adf2f63), [cb6c7a2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cb6c7a2))

### 📝 Документация

- **#31** - проведен глубокий архитектурный аудит документации и синхронизация с реализацией ([86c64e1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/86c64e1))
- **#31** - рефакторинг правил комментариев в AI контракте ([11058cf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/11058cf))
- **#31** - обновлены правила рабочего процесса коммитов ([40897c0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/40897c0))
- **#30** - проведена обезличка документации и обновлены стандарты мутационного тестирования ([8aa279f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8aa279f))
- **#31** - актуализирован AI контракт и документация ([a8289f1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a8289f1), [2a94876](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2a94876))

### 🐛 Исправления

- **#31** - отключены глобальные переменные логов в Buggregator error target ([9e2e1eb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9e2e1eb))
</details>

## [0.16.0] - 2026-01-04 - "Надежность тестов и архитектурная стандартизация"

> Это фундаментальный релиз, в котором проведена полная стандартизация кодовой базы и значительное архитектурное укрепление. Внедрены строгие правила линтинга (PHPCS/Slevomat), достигнуто честное 100% покрытие кода тестами и внедрены продвинутые механизмы внедрения зависимостей через обертки компонентов. Архитектурно осуществлен переход на специализированные DTO для пагинации, стабилизирована работа с PostgreSQL и реализован `TransactionalEventPublisher` для гарантированной доставки событий. Устранены риски переполнения памяти при работе с файлами, внедрен `LogSmsSender` для безопасной разработки и добавлена серия ADR (Architectural Decision Records) для прозрачной истории ключевых решений.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#30** - реализованы **обертки компонентов** для решения рекурсии DI и включено автовайринг инфраструктуры ([075516a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/075516a))
- **#30** - реализована поддержка идентификации на стороне БД через `IdentityAssignmentTrait` и рефлексию ([e458178](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e458178))
- **#30** - внедрены специализированные **DTO для пагинации** (`IndexPaginationRequest`) и рефакторинг контроллеров ([886e71c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/886e71c), [68469ef](https://github.com/WarLikeLaux/yii2-book-catalog/commit/68469ef), [bc1f7e7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bc1f7e7), [0f123fe](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0f123fe))
- **#30** - реализован уникальный индекс для авторов на уровне базы данных ([aecb418](https://github.com/WarLikeLaux/yii2-book-catalog/commit/aecb418))
- **#30** - реализован `TransactionalEventPublisher` для обработки событий после фиксации транзакции ([aa30c81](https://github.com/WarLikeLaux/yii2-book-catalog/commit/aa30c81))
- **#30** - добавлена валидация версии в `BookForm` ([8547e89](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8547e89))
- **#30** - добавлен `LogSmsSender` для безопасной разработки и тестирования ([a3df180](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a3df180))

### 🐛 Исправления

- **#30** - исправлено выполнение консольной команды очереди (удален builtin shell call) ([610ee4b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/610ee4b))
- **#30** - исправлена инициализация базы PostgreSQL и улучшено обнаружение дубликатов ([b600d12](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b600d12))
- **#30** - исправлен баг уникальности ZADD в `RateLimitRepository` ([6d990ef](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6d990ef))
- **#30** - устранен риск OOM (Out-of-Memory) в `LocalFileStorage` при работе с файлами ([48f0736](https://github.com/WarLikeLaux/yii2-book-catalog/commit/48f0736))
- **#30** - исправлена ошибка в хлебных крошках и проведена стабилизация `TracerBootstrap` ([7b7232b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7b7232b), [4330cc4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4330cc4))

### 🛠 Рефакторинг и архитектура

- **#30** - рефакторинг `JobHandlerRegistry` в сервис с поддержкой ленивой загрузки через контейнер ([838062c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/838062c), [ca6557c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ca6557c))
- **#30** - удалены публичные сеттеры ID в доменных сущностях для сохранения инкапсуляции ([9834eaa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9834eaa))
- **#30** - рефакторинг юзкейсов для использования `TransactionalEventPublisher` ([1cbb371](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1cbb371))
- **#30** - удален `BookViewModel` в пользу прямого использования `BookReadDto` в представлениях ([03a3477](https://github.com/WarLikeLaux/yii2-book-catalog/commit/03a3477), [6996304](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6996304))
- **#30** - оптимизация репозиториев для пропуска избыточной валидации ActiveRecord ([09551d4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/09551d4))
- **#30** - рефакторинг команд и удаление запрещенных аннотаций в слое Application ([9d2667e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9d2667e))
- **#30** - удален `BookYearFactory`, юзкейсы переведены на прямое использование `ClockInterface` ([827c8dd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/827c8dd))
- **#30** - валидация `BookYear` сделана опциональной для безопасного восстановления ([e8aade2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e8aade2))
- **#30** - улучшена обработка исключений в юзкейсах авторов ([c3c34cd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c3c34cd))
- **#30** - уточнены типы возвращаемых значений в спецификациях и результатах запросов ([e2d093b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e2d093b), [946b339](https://github.com/WarLikeLaux/yii2-book-catalog/commit/946b339))
- **#30** - стандартизация фильтров представления и виджетов согласно единому стилю кода ([3b4b435](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3b4b435))
- **#30** - очистка ActiveRecord моделей от неиспользуемых методов ([7e949f0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7e949f0))
- **#30** - рефакторинг внедрения зависимостей в конфигурации приложения ([11da0de](https://github.com/WarLikeLaux/yii2-book-catalog/commit/11da0de), [ab7a94e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ab7a94e), [14000e3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/14000e3))

### 🧪 Тестирование

- **#30** - достигнуто **100% покрытие кода тестами** и расширение мутационного тестирования ([23ca2e7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/23ca2e7))
- **#30** - реализован транзакционный `DbCleaner` для надежной очистки базы между тестами ([a246b4d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a246b4d))
- **#30** - внедрен `BookTestHelper` для стандартизированного создания сущностей в юнит-тестах ([547d289](https://github.com/WarLikeLaux/yii2-book-catalog/commit/547d289), [af75f10](https://github.com/WarLikeLaux/yii2-book-catalog/commit/af75f10), [8d96be5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8d96be5))
- **#30** - рефакторинг `IsbnTest` и тестов юзкейсов для улучшения изоляции ([0a39793](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0a39793), [65456af](https://github.com/WarLikeLaux/yii2-book-catalog/commit/65456af), [51460f6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/51460f6))
- **#30** - добавлены тесты для `LogSmsSender` и `NullTracer` ([6dfd8bc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6dfd8bc), [d98a1a1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d98a1a1))
- **#30** - рефакторинг `IdentityAssignmentTraitTest` и обновление тестов после удаления ViewModels ([bc16cee](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bc16cee), [ba4ab1b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ba4ab1b), [fbeb6ae](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fbeb6ae))
- **#30** - удален устаревший тест валидации из `SubscriptionRepositoryTest` ([54fcd5b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/54fcd5b))

### ⚙️ Инфраструктура

- **#30** - внедрена строгая стандартизация стиля кода (PHPCS/Slevomat) ([015a0e5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/015a0e5), [2454127](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2454127), [4e65d37](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4e65d37), [0adb13d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0adb13d), [30e3981](https://github.com/WarLikeLaux/yii2-book-catalog/commit/30e3981), [a094eb1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a094eb1), [d8b0035](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d8b0035))
- **#30** - рефакторинг инфраструктуры и observability для устранения нарушений ([93330a7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/93330a7), [3b373d5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3b373d5), [1ff6c79](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1ff6c79), [ddc116d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ddc116d))
- **#30** - обновление конфигурации **Rector** и статического анализа ([036ab67](https://github.com/WarLikeLaux/yii2-book-catalog/commit/036ab67), [c50cdaa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c50cdaa), [67ea3e3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/67ea3e3))
- **#30** - подавление бесполезных правил для доменных исключений ([e10e910](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e10e910))
- **#30** - обновлена конфигурация DI для использования `LogSmsSender` ([05e6675](https://github.com/WarLikeLaux/yii2-book-catalog/commit/05e6675))

### 📝 Документация

- **#30** - создана серия ADR (Architectural Decision Records) в `docs/DECISIONS.md` ([f6f88c6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f6f88c6), [ed5bc81](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ed5bc81), [946e20b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/946e20b), [c29958a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c29958a))
- **#30** - задокументированы решения по идентификации на стороне БД и группировке хендлеров ([c61cbf1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c61cbf1))
- **#30** - синхронизированы ссылки на ADR в докблоках по всему проекту ([11fc001](https://github.com/WarLikeLaux/yii2-book-catalog/commit/11fc001), [d77ef6f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d77ef6f))
</details>

## [0.15.0] - 2026-01-04 - "Rate Limiting и Readonly"

> Внедрено ограничение скорости запросов (Rate Limiting) для защиты API. Доменные сущности стали иммутабельными благодаря readonly свойствам PHP 8.2+. Добавлен драйвер PSR-20 Clock и View Models для разделения логики представления. Интеграция GLightbox оживила галерею, а Graceful Shutdown сделал воркеры надежнее.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#29** - реализована система **Rate Limiting** (сервис, фильтр, репозиторий) для защиты API ([0b6f985](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0b6f985), [4fcf918](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4fcf918), [dc3f4eb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dc3f4eb), [f1503c7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f1503c7))
- **#29** - внедрена интеграция **GLightbox** для просмотра галереи изображений ([f2a7142](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f2a7142))
- **#29** - добавлен **SystemClock** с реализацией `PSR-20 ClockInterface` ([7572afb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7572afb))
- **#29** - реализован **Graceful Shutdown** для корректного завершения воркеров очереди ([6771295](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6771295))
- **#29** - добавлена фабрика `BookYearFactory` для создания ValueObject года с учетом текущего времени ([42e1738](https://github.com/WarLikeLaux/yii2-book-catalog/commit/42e1738))
- **#29** - добавлен класс `StoredFileReference` для работы с файлами ([aaa427e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/aaa427e))
- **#29** - внедрен **навык README** ([a8d7cf5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a8d7cf5))

### 🐛 Исправления

- **#29** - исправлена обработка исключений в `SubscribeUseCase` ([481ef12](https://github.com/WarLikeLaux/yii2-book-catalog/commit/481ef12))

### 🛠 Рефакторинг и архитектура

- **#29** - доменные сущности переведены на использование **readonly public properties** ([a216a93](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a216a93), [1ef2ac1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1ef2ac1))
- **#29** - внедрены **View Models** для разделения логики представления ([a1897cd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a1897cd))
- **#29** - рефакторинг Query Services и инфраструктурного слоя ([8110549](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8110549))
- **#29** - упрощена спецификация `YearSpecification` ([1a0ca60](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1a0ca60))
- **#29** - оптимизирована проверка существования авторов (batch processing) ([561e455](https://github.com/WarLikeLaux/yii2-book-catalog/commit/561e455))
- **#29** - обновлены Use Cases и Mapper для работы с фабрикой `BookYear` ([265fe1a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/265fe1a), [ed0ecce](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ed0ecce), [3236c80](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3236c80))
- **#29** - удалено избыточное событие `BookCreatedEvent` ([96b10e7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/96b10e7))
- **#29** - оптимизирована загрузка изображений с использованием атрибута **lazy** ([a028426](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a028426))

### 🧪 Тестирование

- **#29** - добавлены тесты для функционала Rate Limiting ([40a26e9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/40a26e9))
- **#29** - добавлены тесты для декоратора трассировки RateLimitRepository ([315cfaa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/315cfaa))
- **#29** - добавлены недостающие тесты для обновления книги и доменных инвариантов ([366979e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/366979e))

### 📝 Документация

- **#29** - обновлен README.md информацией о PSR-20 Clock ([edb3e41](https://github.com/WarLikeLaux/yii2-book-catalog/commit/edb3e41))
- **#29** - добавлен PHPDoc для `HandlerAwareQueue` и `RequestIdProvider` ([586ce9d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/586ce9d))
- **#29** - обновлены примеры архитектуры и рекомендации по документации ([213ad83](https://github.com/WarLikeLaux/yii2-book-catalog/commit/213ad83))
- **#29** - обновлен контракт ([89afe74](https://github.com/WarLikeLaux/yii2-book-catalog/commit/89afe74))
- **#29** - обновлены метрики MSI (Mutation Score Indicator) в README ([ad0ab5f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ad0ab5f))

### ⚙️ Инфраструктура

- **#29** - зарегистрированы `ClockInterface` и `BookYearFactory` в DI контейнере ([19efe02](https://github.com/WarLikeLaux/yii2-book-catalog/commit/19efe02))
- **#29** - обновлена конфигурация `repomix` ([e3dd398](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e3dd398))
- **#29** - обновлены зависимости проекта ([b635ba0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b635ba0))
- **#29** - улучшена валидация документации и **workflow для readme** ([f1a7cc3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f1a7cc3))
- **#29** - обновлены метаданные и заголовки **workflow** ([60b0cf8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/60b0cf8))
</details>

## [0.14.0] - 2026-01-03 - "PostgreSQL, PsySH и Observability"

> Ключевой релиз, внедряющий полноценную поддержку PostgreSQL и мульти-базовую архитектуру. Система стала полностью агностик к базе данных. Инструментарий разработчика вышел на новый уровень с интеграцией PsySH и расширенными возможностями отладки. Значительно улучшена наблюдаемость (Observability) благодаря сквозной трассировке асинхронных операций. Добавлен виджет системной информации и устранены архитектурные ограничения в адаптерах. Весь проект прошел через визуальное обновление документации с новым hero-баннером и улучшенной структурой README.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#28** - добавлена поддержка PostgreSQL и мульти-базовая конфигурация ([08e18bf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/08e18bf))
- **#28** - добавлен интерактивный shell с интеграцией PsySH ([84fc999](https://github.com/WarLikeLaux/yii2-book-catalog/commit/84fc999))
- **#28** - добавлены симлинки для правил агентов в Makefile ([1874e6f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1874e6f))
- **#28** - реализованы декораторы трассировки для очередей и идемпотентности ([2dcf2a2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2dcf2a2))
- **#28** - реализован виджет системной информации ([9ef63d6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9ef63d6))
- **#28** - реализован авто-генератор документации и AI помощники ([50b5a06](https://github.com/WarLikeLaux/yii2-book-catalog/commit/50b5a06))
- **#28** - добавлен индикатор драйвера БД в UI ([571358a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/571358a))

### 🐛 Исправления

- **#28** - исправлено нарушение deptrac в SystemInfoAdapter заменой Yii на BaseYii ([daba0f2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/daba0f2))
- **#28** - исправлены ошибки кода возврата в командах diff Makefile ([c92b98f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c92b98f))

### 🛠 Рефакторинг и архитектура

- **#28** - реализована независимая от БД логика репозитория ([da2b9f4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/da2b9f4))
- **#28** - рефакторинг миграций для совместимости с PostgreSQL ([1837e03](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1837e03))
- **#28** - миграции адаптированы под raw SQL для FULLTEXT индексов ([b244581](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b244581))
- **#28** - рефакторинг NotifySubscribersHandler для использования LoggerInterface ([96c4c43](https://github.com/WarLikeLaux/yii2-book-catalog/commit/96c4c43))
- **#28** - рефакторинг BookYear для использования DateTimeImmutable ([5dc5d43](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5dc5d43))
- **#28** - стандартизированы типы исключений и обновлены тесты BookYear ([321709f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/321709f))
- **#28** - рефакторинг логики обновления книг и улучшения вида авторов ([08a7c2c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/08a7c2c))
- **#28** - добавлен индекс author_id в таблицу book_authors ([a67cc76](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a67cc76))
- **#28** - удален избыточный ключ перевода isbn_exists_generic ([a6ba2dc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a6ba2dc))

### 🧪 Тестирование

- **#28** - добавлены unit-тесты для QueueTracingDecorator ([b55ca5d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b55ca5d))
- **#28** - обновлена конфигурация тестов для PostgreSQL ([f1cb6d0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f1cb6d0))
- **#28** - обновлены тесты и классы поддержки для совместимости с PostgreSQL ([065650a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/065650a))

### 📝 Документация

- **#28** - обновлена архитектурная документация и структурная навигация ([f2d8a39](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f2d8a39))
- **#28** - обновлена проектная документация и воркфлоу разработки с AI ([05f2219](https://github.com/WarLikeLaux/yii2-book-catalog/commit/05f2219))
- **#28** - обновлен основной README: добавлен hero-баннер, архитектурное сравнение и улучшена структура ([c3aaf68](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c3aaf68), [45b71cc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/45b71cc), [41349f6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/41349f6))

### ⚙️ Инфраструктура

- **#28** - обновлен CHANGELOG.md для версии 0.14.0 и доработан воркфлоу генерации лога ([a673543](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a673543))
- **#28** - обновлен Makefile и CI для мульти-БД сред ([17af582](https://github.com/WarLikeLaux/yii2-book-catalog/commit/17af582))
- **#28** - обновлены скрипты Makefile и зависимости ([bc3c22a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bc3c22a))
- **#28** - обновлен воркфлоу коммитов инструкцией по атомарному разделению ([e9c4c39](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e9c4c39))
- **#28** - уточнены инструкции воркфлоу коммитов ([b6a45f4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b6a45f4))
</details>

## [0.13.0] - 2026-01-02 - "Сверхзвуковая идемпотентность, гибкость и точный поиск"

> Масштабный рефакторинг инфраструктуры и внедрение продвинутых паттернов. Реализована полноценная идемпотентность с отслеживанием статусов, внедрены спецификации и политики для чистоты домена. Архитектура стала еще более отчуждаемой благодаря разделению интерфейсов (ISP), маппингу событий и консолидации локализации. Улучшены механизмы поиска и валидации. UI получил мощный заряд динамики с клиентской генерацией данных и современными виджетами.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#27** - улучшен быстрый поиск и исправлен полнотекстовой поиск авторов через MATCH ([6b2f33d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6b2f33d))
- **#27** - внедрен паттерн **Specification** для формализации критериев поиска и фильтрации книг ([460ad6b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/460ad6b))
- **#27** - реализован метод `searchBySpecification` в `BookRepository` ([f74437a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f74437a))
- **#27** - добавлена доменная политика `BookPublicationPolicy` для управления правилами публикации ([460ad6b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/460ad6b))
- **#27** - добавлена логика генерации книг на стороне клиента и ассет `FakerAsset` ([f31325a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f31325a))
- **#27** - добавлены UI компоненты и рефакторинг представлений с использованием виджетов ([43550c2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/43550c2))
- **#27** - реализовано хранилище временных файлов ([8be9c17](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8be9c17))
- **#27** - введена поддержка статусов идемпотентности и DTO для записей ([461e080](https://github.com/WarLikeLaux/yii2-book-catalog/commit/461e080))

### 🛠 Рефакторинг и архитектура

- **#27** - удалено правило уникальности ISBN из сущности Book и обновлен шаблон ActiveField ([58e1cdf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/58e1cdf))
- **#27** - разделены интерфейсы для чтения (Query) и записи (Repository) согласно ISP ([f138a0a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f138a0a))
- **#27** - удалена зависимость от `TranslatorInterface` в репозиториях, реализован возврат ключей сообщений ([1a90348](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1a90348))
- **#27** - реализован **EventToJobMapper** для отвязки доменных событий от конкретных задач очереди ([bab9912](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bab9912))
- **#27** - консолидированы все переводы в `app.php`, удалены разрозненные файлы `domain.php` ([a41f6cd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a41f6cd))
- **#27** - обновлены формы, хендлеры и валидаторы для поддержки унифицированных ключей i18n ([2c3359c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2c3359c))
- **#27** - рефакторинг `BookCommandHandler` для использования `WebUseCaseRunner` ([184acf6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/184acf6))
- **#27** - упрощена сущность `Book` за счет выноса логики в спецификации и политики ([460ad6b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/460ad6b))
- **#27** - рефакторинг валидации в `Isbn.php` для упрощения логики ([b43ed28](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b43ed28))
- **#27** - рефакторинг `BookForm` и добавление unit-тестов для форм ([a2c9955](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a2c9955))
- **#27** - рефакторинг задач очереди: внедрены `JobHandlerRegistry` и `HandlerAwareQueue` ([40a3a8c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/40a3a8c))
- **#27** - рефакторинг `IdempotencyFilter` для корректной обработки запросов, находящихся в процессе выполнения ([a3d6804](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a3d6804))
- **#27** - рефакторинг `IdempotencyService` для поддержки отслеживания статуса запросов ([019e381](https://github.com/WarLikeLaux/yii2-book-catalog/commit/019e381))
- **#27** - реализован репозиторий идемпотентности с поддержкой статуса запроса ([6d64d7a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6d64d7a))
- **#27** - обновлена схема хранения и интерфейс репозитория идемпотентности ([4bd22c6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4bd22c6))
- **#27** - рефакторинг `SiteController` для использования `AuthServiceInterface` ([7250753](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7250753))
- **#27** - внедрен интерфейс `AuthServiceInterface` и адаптер `YiiAuthService` ([1284613](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1284613))
- **#27** - рефакторинг видимости `Book::setId` на private ([d0c38fe](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d0c38fe))
- **#27** - рефакторинг `NotifySingleSubscriberJob` для использования promoted properties ([786d040](https://github.com/WarLikeLaux/yii2-book-catalog/commit/786d040))
- **#27** - консолидация категорий логов в единый класс констант `LogCategory` ([a9e6d86](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a9e6d86))
- **#27** - рефакторинг логики префиксов ISBN и обеспечение конфигурации `IdempotencyFilter` ([ea40aae](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ea40aae))
- **#27** - внедрены константы `EVENT_TYPE` и рефакторинг метода `Book::reconstitute` ([63ec4d8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/63ec4d8))
- **#27** - добавлена сортировка ID авторов в `BookRepository` ([ada8468](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ada8468))

### 🧪 Тестирование

- **#27** - обновлены тесты и Makefile для поддержки новой структуры интерфейсов ([4db00a4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4db00a4))
- **#27** - стандартизованы unit-тесты для обеспечения 100% покрытия и MSI ([b43ed28](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b43ed28))
- **#27** - удален `codeCoverageIgnore` из методов `execute` задач очереди ([dd9870a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dd9870a))
- **#27** - обновлены интеграционные и unit-тесты для идемпотентности и поиска ([7c38c5e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7c38c5e))
- **#27** - добавлены недостающие unit-тесты ([fe27234](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fe27234))

### 🐛 Исправления

- **#27** - исправлена обработка `null` для версии в `OptimisticLockBehavior` ([978c917](https://github.com/WarLikeLaux/yii2-book-catalog/commit/978c917))
- **#27** - исправлены нарушения Deptrac путем переноса `YiiAuthService` в слой адаптеров ([513f555](https://github.com/WarLikeLaux/yii2-book-catalog/commit/513f555))

### 📝 Документация

- **#27** - обновлена ARCHITECTURE.md: добавлен пример Use Case и описана новая структура ([a73529a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a73529a))
- **#27** - актуализированы метрики тестов и MSI в README.md ([be13dcd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/be13dcd))
- **#27** - обновлена документация по архитектуре и ISP рефакторингу интерфейсов ([0d0bc0a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0d0bc0a))
- **#27** - подробно документирован паттерн Specification и роль `EventToJobMapper` в `ARCHITECTURE.md` ([1ecce46](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1ecce46))
- **#27** - актуализированы метрики проекта в `README.md`: **427 тестов**, **940 ассертов** и **100% MSI** ([fabd78d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fabd78d))
- **#27** - актуализирована автогенерируемая документация БД, моделей и маршрутов ([72de8e7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/72de8e7))

### ⚙️ Инфраструктура

- **#27** - обновлена конфигурация DI и удален `.geminiignore` ([c960e85](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c960e85))
- **#27** - обновлена конфигурация проекта и добавлены инструменты сборки ([0325948](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0325948))
- **#27** - добавлена конфигурация идемпотентности в `params.php` ([79ce100](https://github.com/WarLikeLaux/yii2-book-catalog/commit/79ce100))
</details>

## [0.12.0] - 2026-01-01 - "Блокировки и события"

> Внедрена оптимистичная блокировка для предотвращения конфликтов редактирования и механизмы Mutex для контроля конкурентных процессов. Архитектура стала чище: произошел отказ от `UseCaseExecutor` в пользу прямого выполнения UseCase, улучшены DI в репозиториях и поддержка вложенных транзакций.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#26** - реализована **оптимистичная блокировка** для сущности `Book` для защиты от конкурентных правок ([9069ab9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9069ab9))
- **#26** - реализован порт и адаптер **Mutex** для контроля конкурентного доступа ([3b0ac9b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3b0ac9b))
- **#26** - реализована поддержка **асинхронных событий** и улучшена идемпотентность ([3b0ac9b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3b0ac9b))
- **#26** - реализован полнотекстовый поиск авторов через `MATCH AGAINST` ([3fa84ea](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3fa84ea))
- **#26** - реализован сценарий публикации книги `PublishBookUseCase` и соответствующая команда ([023c34d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/023c34d), [0093723](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0093723))
- **#26** - добавлена логика публикации и новые доменные события в сущность `Book` ([144a2c8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/144a2c8))

### 🛠 Рефакторинг и архитектура

- **#26** - удален **UseCaseExecutor** в пользу прямого использования сценариев через `WebUseCaseRunner` ([084b350](https://github.com/WarLikeLaux/yii2-book-catalog/commit/084b350))
- **#26** - рефакторинг репозиториев для использования прямого внедрения зависимостей (**Dependency Injection**) ([edd9a8b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/edd9a8b))
- **#26** - реализована поддержка **вложенных транзакций** в `YiiTransactionAdapter` ([d491fc0](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d491fc0))
- **#26** - упрощена конфигурация контейнера за счет автоматического связывания UseCase и Query ([c092fdb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c092fdb))
- **#26** - рефакторинг `AuthorReadDto` и обновление связанных запросов ([3c7abd7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3c7abd7))

### 🧪 Тестирование

- **#26** - добавлены unit-тесты для `YiiQueueAdapter` ([950e829](https://github.com/WarLikeLaux/yii2-book-catalog/commit/950e829))

### 📝 Документация

- **#26** - актуализированы архитектурные схемы и описание структуры проекта в `ARCHITECTURE.md` ([a92cfaa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a92cfaa))
- **#26** - обновлен `README.md` с актуальными метриками: **394 теста**, **891 ассертов** ([a92cfaa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a92cfaa))

### ⚙️ Инфраструктура

- **#26** - обновлена конфигурация **Deptrac** для корректной работы с адаптерами ([c39d252](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c39d252))
- **#26** - обновлена инфраструктура `EventPublisher` и `FileStorage` ([7411f40](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7411f40))
- **#26** - обновлены зависимости проекта и общая конфигурация ([77d3ce2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/77d3ce2))
</details>

## [0.11.0] - 2025-12-31 - "Чистая валидация"

> Рефакторинг системы валидации и форм. Декораторы трассировки переехали в отдельную директорию. Обновлена конфигурация и тесты.

<details>
<summary>Подробности изменений</summary>

### 🛠 Рефакторинг и архитектура

- **#25** - рефакторинг валидации и форм ([6bec513](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6bec513))
- **#25** - рефакторинг расположения декораторов трассировки ([cf1f985](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cf1f985))

### ⚙️ Инфраструктура

- **#25** - оптимизирована конфигурация infection в CI пайплайне ([cf9427e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cf9427e))
- **#25** - обновлена конфигурация проекта и тесты ([03d8a29](https://github.com/WarLikeLaux/yii2-book-catalog/commit/03d8a29))

### 📝 Документация

- **#25** - обновлен `README.md` ([642117c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/642117c))
- **#25** - обновлен `CHANGELOG.md` ([0b2f1d4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0b2f1d4), [384f907](https://github.com/WarLikeLaux/yii2-book-catalog/commit/384f907))
</details>

## [0.10.0] - 2025-12-30 - "Полноценный домен"

> Наконец-то доменные сущности стали по-настоящему богатыми. Внедрен полноценный Distributed Tracing (Inspector APM) и наблюдаемость. Инфраструктура тестов переведена на современные сьюты (Integration/E2E), покрытие - честные 100%. Плюс Redis-кеширование и правильный Docker-маппинг.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#24** - реализована трассировка **Inspector APM** для мониторинга SQL и HTTP запросов ([a5c4843](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a5c4843))
- **#23** - реализована система наблюдаемости (**Observability**) и воркфлоу для AI-агентов ([884d32e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/884d32e))
- **#22** - реализованы полноценные доменные сущности (**Rich Domain Entities**) для `Book`, `Author` и `Subscription` ([c0fd755](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c0fd755))
- **#20** - реализовано **Redis-кеширование** для отчетов с автоматической инвалидацией при CRUD операциях с книгами ([dcee520](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dcee520))

### 🛠 Рефакторинг и архитектура

- **#23** - рефакторинг валидации доменных сущностей и расширение возможностей `BookYear` ([4543354](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4543354))
- **#23** - рефакторинг `SubscriptionForm` с внедрением `AuthorExistsValidator` для исключения зависимости от инфраструктуры ([7654eeb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7654eeb))
- **#23** - унифицирована обработка ошибок базы данных в репозиториях ([884d32e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/884d32e))
- **#22** - добавлено PHPStan правило `DomainEntitiesMustBePureRule` для проверки чистоты доменных сущностей ([3d698b5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3d698b5))
- **#22** - слой представления реорганизован в модульную структуру (**feature-based**) на основе Handlers и Factories ([6c7c253](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6c7c253))
- **#22** - исправлены стандарты кодирования и устаревшие пространства имен в конфигурации контейнеров ([5b8dddd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5b8dddd), [98ba179](https://github.com/WarLikeLaux/yii2-book-catalog/commit/98ba179))
- **#21** - рефакторинг CLI инструментов в строго типизированные классы ([a6e4236](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a6e4236))
- **#21** - доработаны комментарии и применен единый стиль кода согласно стандартам ([7568f5a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7568f5a))
- **#22** - удалены инлайн-комментарии и неиспользуемые свойства в тестах ([d50d239](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d50d239))

### 🧪 Тестирование

- **#23** - рефакторинг структуры тестов и расширение покрытия интеграционными тестами ([31c07fa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/31c07fa))
- **#23** - добавлен `@codeCoverageIgnore` для инициализации `IsbnValidator` ([4d2b5bb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4d2b5bb))
- **#23** - рефакторинг инфраструктуры тестирования: разделение на интеграционные (Integration) и приемочные (E2E) сьюты ([8c6ebb8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8c6ebb8))
- **#23** - достигнуто **100% покрытие кода тестами** (315 тестов, 673 assertions) ([8513992](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8513992))
- **#22** - достигнуто **100% покрытие кода тестами** (277 тестов, 613 assertions) ([c0fd755](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c0fd755))
- **#21** - добавлены unit-тесты для доменных сущностей и форм: `AuthorTest`, `BookTest`, `SubscriptionTest`, `LoginFormTest`, `ReportFilterFormTest`, `SubscriptionFormTest` ([c0fd755](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c0fd755))
- **#19** - ограничено количество потоков Infection до одного процесса для предотвращения segmentation faults ([5379f6d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5379f6d))

### 🐛 Исправления

- **#22** - исправлена загрузка файлов в `BookController` ([66b0a52](https://github.com/WarLikeLaux/yii2-book-catalog/commit/66b0a52))
- **#22** - исправлена логика определения переменных окружения и работа CI ([d036a80](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d036a80), [71bb8c9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/71bb8c9))
- **#21** - исправлена инициализация Redis в GitHub Actions CI ([dd5f3e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dd5f3e8))

### ⚙️ Инфраструктура

- **#21** - интегрирован Buggregator Trap и улучшены цели логирования ([28ae489](https://github.com/WarLikeLaux/yii2-book-catalog/commit/28ae489))
- **#21** - обновлена конфигурация приложения для использования динамических портов окружения ([ee8c2a8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ee8c2a8))
- **#21** - обновлено окружение разработки (интерактивный лейаут и докер-сервисы) ([eb75684](https://github.com/WarLikeLaux/yii2-book-catalog/commit/eb75684))
- **#22** - добавлено создание пользователя с настраиваемым UID в Docker-образ и маппинг пользователей ([249f93d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/249f93d), [d983da4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d983da4))
- **#22** - добавлен реверс-прокси Nginx для контейнеризированного окружения ([d983da4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d983da4))
- **#22** - нормализованы права доступа к файлам (755 -> 644 для PHP файлов) ([1316967](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1316967))
- **#22** - CI переключен на тестовое окружение и оптимизирован (удален debug-код) ([82a0263](https://github.com/WarLikeLaux/yii2-book-catalog/commit/82a0263), [ec758b1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ec758b1))
- **#22** - добавлен отладочный вывод в CI для приемочных тестов ([3034248](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3034248))
- **#22** - обновлены Makefile и конфигурация CI для обеспечения надежного тестирования ([b58301d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b58301d))
- **#23** - внедрен **GrumPHP** и обновлена инфраструктура сборки ([cbadd4c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cbadd4c))
- **#23** - исправлена конфигурация GitHub CI пайплайна ([2615c0a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2615c0a), [0335a5e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0335a5e))
- **#22** - увеличен лимит коммитов в истории changelog до 100 ([a538ec5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a538ec5))

### 📝 Документация

- **#24** - добавлен раздел **Observability & Tracing** в `README.md` ([3a2dde1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3a2dde1))
- **#23** - актуализировано описание команд Makefile в `README.md` ([4d1357a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4d1357a))
- **#23** - добавлены диаграммы **C4 Model** в `ARCHITECTURE.md` ([7783db5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7783db5))
- **#23** - обновлен `README.md` с актуальными метриками и списком команд ([8513992](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8513992))
- **#22** - обновлен `CHANGELOG.md` (добавлены кодовые имена версий и пропущенные коммиты) ([5535e17](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5535e17))
- **#22** - добавлена документация паттерна Rich Domain Entity ([c0610a1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c0610a1))
- **#22** - синхронизирована документация с актуальным кодом и структурой проекта ([ff70c60](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ff70c60))
- **#22** - обновлен `ARCHITECTURE.md` для отражения реализации Rich Domain Entities ([34ae98f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/34ae98f))
- **#22** - README приведен в соответствие с модульной структурой проекта (секция 12) ([654ae42](https://github.com/WarLikeLaux/yii2-book-catalog/commit/654ae42))
- **#22** - Rich Domain Model исключен из раздела архитектурных компромиссов ([d611f91](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d611f91))
- **#21** - обновлена документация проекта и метрики покрытия ([cf44dc6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cf44dc6))
- **#19** - синхронизированы недостающие хеши в списке изменений ([9e707de](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9e707de))
- **#22** - обновлен CHANGELOG.md для версии 0.10.0 ([2f1b21b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2f1b21b))
</details>

## [0.9.0] - 2025-12-28 - "Идемпотентность"

> HTTP-запросы теперь защищены от дублирования через `Idempotency-Key`. Добавлены строгие правила безопасности PHPStan, внедрен валидатор документации и MSI доведен до 96%. Порядок.

<details>
<summary>Подробности изменений</summary>

### 🛡️ Безопасность

- **#19** - внедрены строгие правила безопасности (`strict-rules`) и исправлены ошибки типизации ([56e4c08](https://github.com/WarLikeLaux/yii2-book-catalog/commit/56e4c08))

### 🐛 Исправления

- **#19** - исправлены выжившие мутанты в валидации ISBN и обработке ошибок ([ec0ea51](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ec0ea51))

### 🚀 Новые функции и возможности

- **#18** - реализована **HTTP Idempotency** через заголовок `Idempotency-Key` для защиты от дублирования запросов ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - добавлен `IdempotencyFilter` для автоматического кеширования ответов POST-запросов ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))

### 🧪 Тестирование

- **#19** - реализован скрипт **автоматической валидации документации** `bin/validate-docs` ([bfbaada](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bfbaada), [d4e2b22](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d4e2b22))
- **#19** - обеспечена строгая синхронизация метрик (тесты, ассерты, файлы) в README через валидатор ([d4e2b22](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d4e2b22))
- **#19** - оптимизирована скорость мутационного тестирования и достигнут **MSI 96%** ([ec0ea51](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ec0ea51))
- **#18** - достигнуто **100% покрытие кода тестами** (238 тестов, 517 assertions) ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - добавлены unit-тесты: `IdempotencyServiceTest`, `BookReadDtoTest`, `SubscribeUseCaseTest`, `YiiTransactionAdapterTest`, `IdempotencyFilterTest`, `LoginPresentationServiceTest` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - добавлены functional-тесты: `IdempotencyCest`, расширены `AuthorRepositoryTest`, `BookRepositoryTest` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - унифицированы аннотации `@codeCoverageIgnore` с русскими пояснениями ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))

### 🛠 Рефакторинг и архитектура

- **#19** - внедрены кастомные **архитектурные правила PHPStan** для контроля чистоты Domain слоя ([fbcaf1f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fbcaf1f))
- **#19** - рефакторинг внедрения зависимостей в инфраструктурном слое ([fbcaf1f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fbcaf1f))
- **#18** - рефакторинг Makefile: новые команды `make dev`, `make ci`, `make pr`, `make fix` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - удалены избыточные `@codeCoverageIgnoreStart/End` блоки в репозиториях ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - упрощена конфигурация CI - coverage берётся из `codeception.yml` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))

### ⚙️ Инфраструктура

- **#19** - увеличен тайм-аут composer для предотвращения ошибок загрузки ([9d8c06b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9d8c06b))
- **#18** - увеличен таймаут для `asset-packagist` и добавлен русский перевод в Dockerfile ([10df45a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/10df45a))
- **#18** - добавлены workflow команды для `commit` и `changelog` ([051a2e5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/051a2e5))
- **#19** - добавлен скрипт `bin/validate-changelog` и workflow шаг для проверки целостности ([74b63d4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/74b63d4), [c0c1fe7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c0c1fe7))

### 📝 Документация

- **#19** - обновлен `README.md` с разделением на Source и Test код/файлы и актуальными метриками ([d4e2b22](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d4e2b22), [ec277bb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ec277bb))
- **#19** - внедрена политика **ZERO TOLERANCE** для проактивных коммитов в AI Контракт ([d4e2b22](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d4e2b22))
- **#19** - обновлена спецификация OpenAPI с русскими переводами ([5a9d4bf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5a9d4bf))
- **#19** - обновлены метрики тестирования в README (249 тестов, 96% MSI) ([dde5714](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dde5714))
- **#19** - обновлен README.md информацией о безопасности и новых стандартах качества ([79f7e20](https://github.com/WarLikeLaux/yii2-book-catalog/commit/79f7e20))
- **#18** - добавлены русские комментарии в конфиги тестов и `OpenApiSpec` ([10df45a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/10df45a))
- **#18** - добавлены схемы `Book` и `PaginationMeta` в OpenAPI спецификацию ([5ad416a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5ad416a))
- **#18** - обновлен `CHANGELOG.md` ([051a2e5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/051a2e5))
- **#17** - исправлена нумерация версий и выполнено слияние разделов в CHANGELOG.md ([26d4d9f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/26d4d9f), [8d5b8e1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/8d5b8e1))
- **#18** - обновлен README: актуальная статистика (238 тестов, 100% coverage), новые команды ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
- **#18** - обновлен `contract.md`: добавлены команды `make dev/ci/pr/fix` ([2e3eff4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e3eff4))
</details>

## [0.8.0] - 2025-12-27 - "REST & Rector"

> REST API для книг с OpenAPI-документацией и Swagger. Rector автоматом причесал код под PHP 8.4. CI научился запускать Selenium и приёмочные тесты. MSI 92%.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#17** - реализован **REST API** для книг с поддержкой OpenAPI спецификации ([4ac7aa2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4ac7aa2))
- **#17** - внедрена автоматическая генерация документации Swagger и настроены заголовки безопасности (HSTS, CSP, X-Frame-Options) ([4ac7aa2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4ac7aa2))

### 🛠 Рефакторинг и архитектура

- **#16** - внедрен **Rector** для автоматического рефакторинга под стандарты **PHP 8.4** (readonly классы, типизация) ([9351974](https://github.com/WarLikeLaux/yii2-book-catalog/commit/9351974))
- **#16** - обновлен `composer.json` для поддержки PHP 8.4 и стабилизации зависимостей ([ce50a44](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ce50a44))
- **#15** - оптимизирован CI пайплайн: добавлено кеширование зависимостей Composer ([f5eb0fa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f5eb0fa))
- **#15** - внедрено архитектурное тестирование с Deptrac и перенесен IsbnValidator в слой Application ([999573c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/999573c))
- **#15** - внедрена строгость PHPStan уровня 9 по всей кодовой базе ([cfdab6e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cfdab6e))
- **#15** - внедрены строгие правила линтинга и добавлены русские комментарии в конфиг PHPCS ([0f308f5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0f308f5))

### ⚙️ Инфраструктура и надежность

- **#17** - добавлен нагрузочный тест (**k6**) для проверки производительности API ([4ac7aa2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4ac7aa2))
- **#16** - исправлена конфигурация хоста **Selenium** в CI и удален конфликтующий модуль Yii2 из acceptance suite ([f27436e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f27436e))
- **#16** - настроен запуск фонового PHP-сервера и **Selenium** для полноценного выполнения приемочных тестов в CI ([0649d1e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0649d1e))
- **#16** - настроен запуск Infection с ограничением сьютов (`functional,unit`) для стабильности CI ([0376291](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0376291))
- **#15** - внедрен аудит безопасности (`composer audit`) в CI пайплайн ([206eb2f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/206eb2f))
- **#16** - исправлены и улучшены CI workflow файлы (синтаксис команд, workflow_dispatch) ([4661af4](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4661af4))
- **#16** - включен отладочный вывод для приемочных тестов для диагностики сбоев в CI ([223e1ed](https://github.com/WarLikeLaux/yii2-book-catalog/commit/223e1ed))
- **#16** - настроен запуск фонового PHP-сервера для приемочных тестов в CI ([bcc96c7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bcc96c7))
- **#15** - добавлена команда `make check` для комплексной проверки качества (lint, analyze, test, audit) ([544e660](https://github.com/WarLikeLaux/yii2-book-catalog/commit/544e660))
- **#15** - добавлен CI workflow для GitHub Actions и улучшена портативность docker-compose ([6d044e9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6d044e9))
- **#16** - рефакторинг синтаксиса CI workflow и добавлен workflow_dispatch ([698f10f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/698f10f))

### 🧪 Тестирование

- **#15** - улучшен **Mutation Score Indicator (MSI)** до **92%** за счет покрытия граничных случаев ([544e660](https://github.com/WarLikeLaux/yii2-book-catalog/commit/544e660))
- **#15** - исправлена загрузка переменных окружения (`.env`) в тестах ([5adf2ef](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5adf2ef))
- **#15** - удален сидинг базы данных из CI для предотвращения загрязнения тестовых данных ([d42971a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d42971a))
- **#15** - исключены views, controllers, forms и AR модели из покрытия unit-тестами ([32ddfae](https://github.com/WarLikeLaux/yii2-book-catalog/commit/32ddfae))
- **#15** - добавлены комплексные unit-тесты слоя Application для author и book commands/use cases ([45c6493](https://github.com/WarLikeLaux/yii2-book-catalog/commit/45c6493))

### 📝 Документация

- **#17** - обновлена автогенерируемая документация схемы БД, моделей и маршрутов ([ff0a75b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ff0a75b))
- **#16** - исправлена навигация и обработка внешних ссылок в документации ([cba78e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/cba78e8), [47bc9e6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/47bc9e6))
- **#16** - исправлена ссылка в подвале для открытия в новой вкладке ([97010c6](https://github.com/WarLikeLaux/yii2-book-catalog/commit/97010c6))
- **#16** - обновлена статистика проекта и оформление команд в README ([1af7cdf](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1af7cdf))
- **#16** - выполнен откат HTML-ссылок на Markdown для соблюдения политики безопасности GitHub ([904d466](https://github.com/WarLikeLaux/yii2-book-catalog/commit/904d466))
- **#15** - интегрированы архитектурные диаграммы и документация по безопасности ([17b0075](https://github.com/WarLikeLaux/yii2-book-catalog/commit/17b0075))
</details>

## [0.7.0] - 2025-12-27 - "Value Objects"

> Сервисы разделены на Command и View, внедрены Isbn и BookYear как Value Objects. Добавлено 100+ новых тестов, покрытие выросло с 76% до 88%. Устранен анти-паттерн "Supervisor Controller".

<details>
<summary>Подробности изменений</summary>

### 🛠 Рефакторинг и архитектура

- **#14** - полное разделение Presentation Services на **Command Services** (Write) и **View Services** (Read) для всех контроллеров (Books, Authors, Subscriptions) ([fb0a11c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fb0a11c))
- **#14** - внедрение Value Objects (`Isbn`, `BookYear`) для инкапсуляции бизнес-правил валидации ([70df022](https://github.com/WarLikeLaux/yii2-book-catalog/commit/70df022))
- **#14** - устранение анти-паттерна "Supervisor Controller" и удаление монолитных FormPreparationService ([fb0a11c](https://github.com/WarLikeLaux/yii2-book-catalog/commit/fb0a11c))
- **#14** - перенесена обработка обложки из BookFormMapper в BookFormPreparationService ([be61f9b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/be61f9b))

### ⚙️ Инфраструктура и надежность

- **#14** - реализована **идемпотентность** отправки SMS (через Cache Lock) для защиты от дублей при ретраях очереди ([1564e15](https://github.com/WarLikeLaux/yii2-book-catalog/commit/1564e15))
- **#14** - добавлены архитектурные комментарии (Technical Debt) касательно Transactional Outbox, Service Locator в Job-ах и Stateful адаптеров ([bcab899](https://github.com/WarLikeLaux/yii2-book-catalog/commit/bcab899))
- **#14** - добавлен repomix таргет и конфигурационный файл в Makefile ([d056ce2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d056ce2))

### 🧪 Тестирование

- **#14** - добавлено **100+ новых тестов**, покрытие кода выросло с **~76%** до **~88%** ([0458b42](https://github.com/WarLikeLaux/yii2-book-catalog/commit/0458b42))
- **#14** - Unit-тесты для: YiiPsrLogger, Queue Jobs, User, Subscription, PagedResultDataProvider, AuthorSelect2Mapper, UseCaseExecutor (query), QueryResult, валидаторов (UniqueIsbn, AuthorExists, UniqueFio, Isbn), форм (BookForm, SubscriptionForm, ReportFilterForm)
- **#14** - Functional-тесты для: CRUD Book/Author, Use Cases (Update/Delete Book, Author Use Cases), SubscriptionController, SiteController, SubscriptionViewService
- **#14** - исправлен баг в `UpdateBookUseCase` - добавлены недостающие импорты Value Objects (`BookYear`, `Isbn`)

### 📝 Документация

- **#14** - обновлен README: актуализирована структура проекта, описано разделение сервисов и использование DDD Value Objects ([a83f74d](https://github.com/WarLikeLaux/yii2-book-catalog/commit/a83f74d))
- **#14** - обновлена статистика тестов в README: 161 тест, 287 assertions, ~88% покрытие ([28c4fd7](https://github.com/WarLikeLaux/yii2-book-catalog/commit/28c4fd7))
- **#14** - добавлена архитектурная документация и обновлен README.md ([36ca2fc](https://github.com/WarLikeLaux/yii2-book-catalog/commit/36ca2fc))
- **#14** - обновлен README.md ([5f6ac06](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5f6ac06), [7eb7350](https://github.com/WarLikeLaux/yii2-book-catalog/commit/7eb7350), [208230f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/208230f))
</details>

## [0.6.0] - 2025-12-25 - "Clean Layers"

> Глобальный рефакторинг на слои Clean Architecture. Selenium в docker-compose, TranslatorInterface для независимых переводов. Проведена очистка легаси - удалены Vagrant и .bowerrc.

<details>
<summary>Подробности изменений</summary>

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
- **#13** - создан CHANGELOG.md для документирования значимых изменений проекта ([deb21ae](https://github.com/WarLikeLaux/yii2-book-catalog/commit/deb21ae))
</details>

## [0.5.0] - 2025-12-22 - "UseCaseExecutor"

> Появился UseCaseExecutor - стандартизированное выполнение бизнес-логики. Пагинация, динамическое кеширование схемы БД, рефакторинг контроллеров на Presentation Services.

<details>
<summary>Подробности изменений</summary>

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
</details>

## [0.4.0] - 2025-12-21 - "Commands & Queries"

> Полный переход на Command/Query/UseCase. Удален старый слой сервисов, внедрены Rich Models. Строгая типизация везде.

<details>
<summary>Подробности изменений</summary>

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
</details>

## [0.3.0] - 2025-12-04 - "BookSearch"

> Добавлена модель BookSearch и интегрирован поиск в SiteController.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности

- **#4** - внедрена модель BookSearch и интегрирована функциональность поиска в SiteController ([aacfa95](https://github.com/WarLikeLaux/yii2-book-catalog/commit/aacfa95))

### 📝 Документация

- **#5** - обновлен README: отражено изменение названия проекта и архитектурные улучшения ([79dea5e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/79dea5e))

### 🧹 Очистка

- **#4** - удалена лишняя пустая строка в файле миграции ([085f32b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/085f32b))
</details>

## [0.2.0] - 2025-12-03 - "SMS & Очереди"

> PSR-логирование для SMS, валидация ISBN, Select2 для авторов, нормализация телефонов E164. Fan-out паттерн в очереди - NotifySingleSubscriberJob.

<details>
<summary>Подробности изменений</summary>

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
</details>

## [0.1.0] - 2025-12-02 - "Hello World"

> Стартовая точка. Каталог книг на Yii2 + PHP 8.4, Docker Compose с PHP/MySQL/Queue, базовый CRUD и сидинг.

<details>
<summary>Подробности изменений</summary>

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
</details>
