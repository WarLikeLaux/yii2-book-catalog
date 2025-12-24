# Список изменений (Changelog)

Все значимые изменения в этом проекте документируются в данном файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [0.6.0] - 2025-12-25

### 🚀 Новые функции и возможности
- **#12** - добавлена поддержка TranslatorInterface и адаптер YiiTranslatorAdapter для независимых переводов ([27378fb](https://github.com/WarLikeLaux/yii2-book-catalog/commit/27378fb))
- **#12** - добавлен сервис Selenium в docker-compose для приемочного тестирования ([77f05bd](https://github.com/WarLikeLaux/yii2-book-catalog/commit/77f05bd))

### 🛠 Рефакторинг и Архитектура
- **#12** - глобальный рефакторинг структуры проекта на слои Clean Architecture (application, domain, infrastructure, presentation) ([dba5729](https://github.com/WarLikeLaux/yii2-book-catalog/commit/dba5729))
- **#12** - настроена инфраструктура покрытия кода (pcov) и внедрены строгие типизированные тесты с поддержкой локализации ([96c589b](https://github.com/WarLikeLaux/yii2-book-catalog/commit/96c589b))

### ⚙️ Инфраструктура и Очистка
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

### 🛠 Рефакторинг и Архитектура
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

### 🛠 Рефакторинг и Архитектура
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