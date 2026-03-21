# Глоссарий: термины и аналогии из Yii2

[← Назад к оглавлению](../learning.md)

---

Если ты писал на Yii2 и впервые видишь термины вроде "Value Object" или "Port" — эта таблица поможет. Для каждого понятия: определение, аналогия из привычного Yii2 и ссылка на главу, где оно разбирается подробно.

## Термины по слоям

### Domain layer

| Термин | Что это | Аналогия из Yii2 | Подробнее |
|--------|---------|-------------------|-----------|
| **Entity** | Объект с уникальным ID и поведением. Содержит бизнес-правила, валидирует инварианты. | `ActiveRecord`, но без `save()`, `find()`, `rules()` и без знания о БД. Только данные и бизнес-логика. | [Глава 3](03-domain.md#entity-rich-model) |
| **Value Object** | Иммутабельный объект без ID, определяемый значением. Два объекта с одинаковым значением — равны. | Нет прямого аналога. Ближайшее — когда ты пишешь `if (strlen($isbn) !== 13)` в контроллере. Value Object перемещает эту проверку в конструктор объекта, и невалидное значение просто не может существовать. | [Глава 3](03-domain.md#value-objects) |
| **Domain Event** | Факт, который произошёл в домене. Сущность записывает его, но не обрабатывает. | `afterSave()` в ActiveRecord, но вместо прямого вызова `Yii::$app->queue->push()` сущность просто говорит: "статус изменился". Кто и как на это реагирует — не её дело. | [Глава 3](03-domain.md#domain-events) |
| **Domain Service** | Бизнес-логика, которая не принадлежит одной сущности. | Метод в `BookService`, который проверяет несколько условий из разных полей перед публикацией. Разница: Domain Service не знает про БД и HTTP. | [Глава 3](03-domain.md#domain-service) |
| **Specification** | Объект, описывающий критерий поиска. | `Book::find()->where(['year' => 2024])` — это SQL. Specification — то же самое, но без SQL: `new YearSpecification(2024)`. SQL появляется потом, в Infrastructure. | [Глава 3](03-domain.md#specification-pattern) |
| **Rich Model** | Entity с методами-действиями (не просто геттеры/сеттеры). Противоположность Anemic Model. | ActiveRecord, у которого `publish()` проверяет бизнес-правила, а не просто ставит `$this->status = 'published'`. | [Глава 3](03-domain.md#entity-rich-model) |
| **Anemic Model** | Объект без поведения — только данные. Логика живёт снаружи (в контроллере или сервисе). | Типичный Yii2 ActiveRecord: `$model->status = 'published'; $model->save();` — модель ничего не проверяет, вся логика в контроллере. | [Глава 3](03-domain.md#entity-rich-model) |
| **Invariant** | Правило, которое должно быть истинным всегда. Если нарушено — объект невалиден. | `rules()` в ActiveRecord, но защищённый не формой, а самим объектом. Например: "у опубликованной книги обязательно есть автор" — это инвариант, который Entity проверяет при каждом изменении. | [Глава 3](03-domain.md#бизнес-правила-в-методах) |

### Application layer

| Термин | Что это | Аналогия из Yii2 | Подробнее |
|--------|---------|-------------------|-----------|
| **Use Case** | Один класс = одна операция. Координирует работу: достаёт сущности, вызывает их методы, сохраняет. | Метод `createBook()` в `BookService`. Разница: Use Case не знает про Yii2, HTTP, сессии. Зависит только от интерфейсов. | [Глава 4](04-application.md#use-case) |
| **Command DTO** | Входные данные для Use Case. `readonly`, строго типизированный, без логики. | Массив параметров, который ты передаёшь в сервис: `$service->create($title, $year, $isbn)`. Command — то же, но типизировано и названо: `new CreateBookCommand(title: ..., year: ...)`. | [Глава 4](04-application.md#command-dto) |
| **Query DTO** | Данные для чтения. `readonly`, без бизнес-логики. | Массив из `ActiveRecord::toArray()`. Query DTO — то же, но с типами: `$dto->title` — точно строка, `$dto->year` — точно int. | [Глава 4](04-application.md#query-dto) |
| **Port** | Интерфейс к внешнему миру. Application определяет "что мне нужно", Infrastructure реализует "как это сделать". | `Yii::$app->db`, `Yii::$app->cache` — ты обращаешься к компоненту. Port — то же, но через интерфейс: `TransactionInterface`, `CacheInterface`. Можно подменить реализацию без правки бизнес-кода. | [Глава 4](04-application.md#ports--интерфейсы-к-внешнему-миру) |
| **Pipeline / Middleware** | Цепочка обработчиков, оборачивающих вызов Use Case. | `behaviors()` в контроллере Yii2 — `AccessControl`, `VerbFilter`. Pipeline — аналог, но для бизнес-операций: транзакция, трейсинг, маппинг ошибок. | [Глава 4](04-application.md#pipeline-и-middleware) |

### Infrastructure layer

| Термин | Что это | Аналогия из Yii2 | Подробнее |
|--------|---------|-------------------|-----------|
| **Repository** | Абстракция коллекции сущностей. Скрывает как именно данные хранятся. | `Book::find()` и `$model->save()` — ты уже пользуешься репозиторием, просто он встроен в ActiveRecord. Здесь он выделен в отдельный класс с явным интерфейсом. | [Глава 5](05-infrastructure.md#repository-pattern) |
| **Hydrator** | Компонент, перекладывающий данные между Entity и ActiveRecord. | `$model->setAttributes($data)` — загрузка данных в AR. Hydrator делает то же, но между доменной сущностью и AR: разворачивает Value Objects, маппит поля. | [Глава 5](05-infrastructure.md#activerecordhydrator) |
| **Identity Map** | Кэш сущностей в памяти. Гарантирует, что один и тот же объект из БД — один и тот же объект в коде. | `Yii::$app->get('component')` всегда возвращает один объект (singleton). Identity Map — то же для сущностей: `$repo->get(42)` всегда возвращает один и тот же объект `Book`. | [Глава 5](05-infrastructure.md#baseactiverecordrepository) |
| **Query Service** | Read-only сервис для выборки данных. Возвращает DTO, не Entity. | `Book::find()->where(...)->all()` — но вынесенный в отдельный класс, возвращающий типизированные DTO вместо массивов AR. | [Глава 5](05-infrastructure.md#query-services) |
| **Adapter** | Обёртка над компонентом Yii2, реализующая интерфейс (Port). | `Yii::$app->cache->get('key')` — прямой вызов. Adapter: `$this->cache->get('key')` — тот же вызов, но через интерфейс `CacheInterface`. Можно заменить Redis на Memcached без правки бизнес-кода. | [Глава 5](05-infrastructure.md#адаптеры-yii2) |
| **Decorator** | Обёртка, добавляющая поведение без изменения оригинала. | `behaviors()` в AR. Декоратор — аналог: `ReportQueryServiceCachingDecorator` оборачивает `ReportQueryService` и добавляет кэширование к каждому вызову. Оригинал не знает об обёртке. | [Глава 5](05-infrastructure.md#декораторы) |

### Presentation layer

| Термин | Что это | Аналогия из Yii2 | Подробнее |
|--------|---------|-------------------|-----------|
| **ViewModel** | Типизированный объект с данными для View. | `$this->render('view', ['model' => $model, 'authors' => $authors])` — массив переменных. ViewModel — то же, но типизированно: `$viewModel->book->title` вместо `$model['title']`. IDE подсказывает поля, опечатка = ошибка компиляции. | [Глава 6](06-presentation.md#viewmodel-pattern) |
| **CommandHandler** | Связующее звено между формой Yii2 и Use Case. Обрабатывает файлы, маппит данные. | Код между `$form->validate()` и `return $this->redirect()` в контроллере — загрузка файла, подготовка данных, вызов сервиса. CommandHandler — тот же код, но в отдельном классе. | [Глава 6](06-presentation.md#commandhandler) |
| **CommandMapper** | Преобразует данные формы в Command DTO. | `new CreateBookCommand($form->title, ...)` — ручной маппинг. Mapper — выделенный класс для этого преобразования. | [Глава 6](06-presentation.md#commandmapper) |
| **Form** | Объект валидации ввода. Только правила, без бизнес-логики. | `Model` в Yii2 — ровно то же самое. Разница: Form не является ActiveRecord и не сохраняет данные. | [Глава 6](06-presentation.md#form) |

### Общие термины

| Термин | Что это | Аналогия из Yii2 |
|--------|---------|-------------------|
| **Clean Architecture** | Архитектура с правилом: зависимости направлены внутрь. Бизнес-логика не знает про фреймворк. | Нет аналога. В Yii2 всё зависит от фреймворка. Clean Architecture — способ изолировать бизнес-логику. |
| **DDD (Domain-Driven Design)** | Подход к проектированию, где код отражает бизнес-домен. Entity, Value Object, Domain Event — из DDD. | Нет аналога. Yii2 ориентирован на данные (таблицы → модели), DDD — на бизнес-логику (правила → объекты). |
| **CQRS (упрощённый)** | Разделение операций чтения и записи. Write через Use Case + Repository, Read через Query Service. | `Book::findOne(42)` для чтения и `$model->save()` для записи — уже разные операции. CQRS формализует это разделение: разные классы, разные модели данных. |
| **Dependency Inversion** | Зависимость от абстракции (интерфейса), а не от конкретного класса. | `Yii::$app->mailer->compose()->send()` — зависимость от конкретного мейлера. Inversion: `$this->mailer->send()` — зависимость от `MailerInterface`, реализация подставляется через DI. |
| **ISP (Interface Segregation)** | Много маленьких интерфейсов лучше одного большого. | Один `BookService` с 20 методами. ISP: `BookRepositoryInterface` (write), `BookFinderInterface` (read by ID), `BookSearcherInterface` (search). Каждый клиент зависит только от нужных методов. |

---

[Далее: Проблема классического Yii2 →](01-problem.md)
