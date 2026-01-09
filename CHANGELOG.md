# Список изменений (Changelog)

[← Назад в README](README.md)

Все значимые изменения в этом проекте документируются в данном файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.18.0] - 2026-01-09 - "Декларативный маппинг, CAS-хранилище и укрепление инфраструктуры"

> Масштабное обновление, охватывающее более 110 коммитов: внедрен AutoMapper для декларативного маппинга DTO и механизм ActiveRecordHydrator для автоматизации сохранения сущностей. Реализована система хранения CAS (Content-Addressable Storage) и механизмы идемпотентности для повышения надежности очередей. Архитектура ядра формализована в ADR #13, усилена строгими правилами PHPStan и унифицированной обработкой ошибок через UseCaseHandlerTrait. Проведен масштабный рефакторинг Makefile и инструментов тестирования для стабилизации CI/CD.

<details>
<summary>Подробности изменений</summary>

### 🚀 Новые функции и возможности
- **#32** - внедрена библиотека **AutoMapper** и атрибуты `MapTo` для декларативного преобразования объектов ([c1e465e](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c1e465e), [21be436](https://github.com/WarLikeLaux/yii2-book-catalog/commit/21be436), [639f3ff](https://github.com/WarLikeLaux/yii2-book-catalog/commit/639f3ff))
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

### 🧪 Тестирование
- **#32** - внедрен **RemovesDirectoriesTrait** для декларативного удаления директорий в тестах ([57b1052](https://github.com/WarLikeLaux/yii2-book-catalog/commit/57b1052))
- **#32** - расширено покрытие тестами для новых инфраструктурных компонентов, мапперов и базовых сервисов ([5e72a89](https://github.com/WarLikeLaux/yii2-book-catalog/commit/5e72a89), [581f8af](https://github.com/WarLikeLaux/yii2-book-catalog/commit/581f8af), [c3434b8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c3434b8), [12fc530](https://github.com/WarLikeLaux/yii2-book-catalog/commit/12fc530), [52dd179](https://github.com/WarLikeLaux/yii2-book-catalog/commit/52dd179))
- **#32** - обновлена конфигурация инструментов тестирования и добавлены вспомогательные ресурсы ([10d5a30](https://github.com/WarLikeLaux/yii2-book-catalog/commit/10d5a30))
- **#32** - стабилизированы тесты идемпотентности, очереди уведомлений и поиска при валидационных ошибках ([f9ca737](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f9ca737), [2e4bb43](https://github.com/WarLikeLaux/yii2-book-catalog/commit/2e4bb43), [c2705ba](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c2705ba), [4d868e1](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4d868e1))
- **#32** - обновлен мок хранилища с обложками в тестах хендлеров ([86f72d5](https://github.com/WarLikeLaux/yii2-book-catalog/commit/86f72d5))
- **#32** - удалены неиспользуемые DTO (AuthorSearchResponse) и обновлены тесты IdentifiableEntity ([b7e96e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/b7e96e8), [4c568c3](https://github.com/WarLikeLaux/yii2-book-catalog/commit/4c568c3))
- **#32** - улучшена надежность и читаемость unit-тестов: удалены неиспользуемые хелперы и добавлены строгие ассерты ([3cd12e8](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3cd12e8), [342d7fa](https://github.com/WarLikeLaux/yii2-book-catalog/commit/342d7fa), [e2dcc83](https://github.com/WarLikeLaux/yii2-book-catalog/commit/e2dcc83))

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

### 📝 Документация
- **#32** - добавлен **ADR #13** о принципах построения инфраструктурного ядра ([c92ab94](https://github.com/WarLikeLaux/yii2-book-catalog/commit/c92ab94))
- **#31** - актуализирована документация проекта и реализован лендинг ([f82832a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/f82832a), [ba9ca50](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ba9ca50))
- **#32** - доработан **DI-контейнер** и конфигурация репозиториев ([6dd0e8a](https://github.com/WarLikeLaux/yii2-book-catalog/commit/6dd0e8a))
- **#32** - рефакторинг поиска и бизнес-логики для соответствия новым сервисам ([ad616d2](https://github.com/WarLikeLaux/yii2-book-catalog/commit/ad616d2))
- **#32** - добавлена поддержка **Generics** для PagedResult и QueryResult ([33a23e9](https://github.com/WarLikeLaux/yii2-book-catalog/commit/33a23e9))
- **#31** - обновлен **CHANGELOG.md** с исправлением разметки и добавлением пропущенных коммитов ([d1126db](https://github.com/WarLikeLaux/yii2-book-catalog/commit/d1126db), [3a61e4f](https://github.com/WarLikeLaux/yii2-book-catalog/commit/3a61e4f))
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
- **#27** - исправлены нарушения Deptrac путем переноса `YiiAuthService` в слой адаптеров ([513f555](https://github.Hcom/WarLikeLaux/yii2-book-catalog/commit/513f555))

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
- **#22** - Rich Domain Model исключен из раздела архитектурных компромиссов ([d611f91](https://github.Hcom/WarLikeLaux/yii2-book-catalog/commit/d611f91))
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
- **#17** - внедрена автоматическая генерация документации Swagger и настроены заголовки безопасности (HSTS, CSP, X-Frame-Options) ([4ac7aa2](https://github.Hcom/WarLikeLaux/yii2-book-catalog/commit/4ac7aa2))

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