# Эволюция архитектуры: от толстого контроллера к Clean Architecture

Сравнение трёх подходов на реальных примерах из этого проекта.

## 📌 Навигация
- [🎯 Главное правило](#-главное-правило-clean-architecture)
- [🗺 Визуализация слоев (Mermaid)](#-визуализация-слоев-mermaid)
- [📊 Три уровня организации кода](#-три-уровня-организации-кода)
- [🔄 Пример: Создание книги (Уровни 1, 2, 3)](#-пример-создание-книги)
- [📈 Сравнительная таблица](#-сравнительная-таблица)
- [🧩 Разбор паттернов (Было → Стало)](#-каждый-паттерн-было--стало)
- [📁 Структура проекта](#-структура-этого-проекта)

---

## 🎯 Главное правило Clean Architecture

> **Бизнес-логика не знает, как её вызывают и куда сохраняют данные.**

```
Внешние слои (зависят от Yii2):
┌────────────────────────────────────────────────────────────┐
│  PRESENTATION   │ Controllers, Forms, Views, Validators   │
├────────────────────────────────────────────────────────────┤
│  INFRASTRUCTURE │ ActiveRecord, Queue, Repositories       │
└────────────────────────────────────────────────────────────┘
                              ↓ зависят от ↓
Внутренние слои (чистый PHP, без Yii):
┌────────────────────────────────────────────────────────────┐
│  APPLICATION    │ UseCases, Commands, Queries, Ports      │
├────────────────────────────────────────────────────────────┤
│  DOMAIN         │ Value Objects, Events, Exceptions       │
└────────────────────────────────────────────────────────────┘
```

### 🗺 Визуализация слоев (Mermaid)

```mermaid
graph TD
    subgraph Presentation ["Presentation Layer (Yii2)"]
        Controllers[Controllers]
        Views[Views]
        Forms[Forms]
    end

    subgraph Application ["Application Layer"]
        UseCases[UseCases]
        Commands[Commands]
        Queries[Queries/DTO]
        UseCases --> Commands
    end

    subgraph Domain ["Domain Layer (Pure PHP)"]
        VO[Value Objects]
        Events[Domain Events]
        Exceptions[Domain Exceptions]
    end

    subgraph Infrastructure ["Infrastructure Layer"]
        Repositories[Repositories]
        ActiveRecord[ActiveRecord Models]
        ExternalAPI[External APIs]
        Queue[Queue Jobs]
    end

    %% Dependencies
    Controllers --> UseCases
    Controllers --> Forms
    UseCases --> Domain
    UseCases --> Repositories
    Repositories --> ActiveRecord
    Infrastructure -- Implements --> Ports[Interfaces in Application]
```

### 🎯 Основные принципы реализации

1. **Инверсия зависимостей (DIP)**: Слой Application не зависит от Infrastructure. Вместо этого он определяет интерфейсы (Ports), которые Infrastructure реализует. Это позволяет легко заменить MySQL на PostgreSQL или SMS-провайдера без изменения бизнес-логики.
2. **Тонкие контроллеры и AR**: Yii2 ActiveRecord используется **только** в слое Infrastructure как детали хранения. В контроллерах нет прямого обращения к моделям для записи или сложной выборки.
3. **Предсказуемость (Value Objects)**: Данные всегда валидны. Если объект `Isbn` или `BookYear` создан — значит данные в нем корректны. Это избавляет от тысяч проверок `if` в коде.

### Что это значит?

**UseCase (`CreateBookUseCase`) не знает:**
- Это HTTP-запрос или CLI-команда?
- Данные из HTML-формы или из REST API?
- Сохраняем в MySQL, PostgreSQL или MongoDB?
- SMS шлём через Twilio или пишем в файл?

**Почему Presentation и Infrastructure зависят от Yii2 — это нормально:**
- Presentation = интерфейс с пользователем. Контроллеры, формы, виджеты — это Yii2.
- Infrastructure = реализация хранения. ActiveRecord, Queue — это тоже Yii2.
- Это **внешние слои** — они по определению зависят от технологий.

**Почему Application и Domain чистые — это критично:**
- Можно перенести в Symfony/Laravel без изменений.
- Можно тестировать без базы данных и HTTP.
- Бизнес-правила не меняются при смене фреймворка.

---

## 📊 Три уровня организации кода

| Уровень | Подход | Типичный проект |
|---------|--------|-----------------|
| **1** | Толстый контроллер | Новичок, быстрый прототип |
| **2** | Контроллер + Сервис | Большинство Yii2/Laravel проектов |
| **3** | Clean Architecture | Enterprise, сложная бизнес-логика |

---

## 🔄 Пример: Создание книги

### Уровень 1: Толстый контроллер

```php
// controllers/BookController.php
public function actionCreate()
{
    $model = new Book();
    
    if ($model->load(Yii::$app->request->post())) {
        // Загрузка файла
        $file = UploadedFile::getInstance($model, 'coverFile');
        if ($file) {
            $path = 'uploads/' . uniqid() . '.' . $file->extension;
            $file->saveAs(Yii::getAlias('@webroot/' . $path));
            $model->cover_url = '/' . $path;
        }
        
        // Валидация ISBN (копипаста из интернета)
        $isbn = str_replace(['-', ' '], '', $model->isbn);
        if (strlen($isbn) !== 13 || !ctype_digit($isbn)) {
            $model->addError('isbn', 'Неверный ISBN');
        }
        
        if (!$model->hasErrors() && $model->save()) {
            // Синхронизация авторов
            Yii::$app->db->createCommand()
                ->delete('book_authors', ['book_id' => $model->id])
                ->execute();
            foreach ($model->authorIds as $authorId) {
                Yii::$app->db->createCommand()->insert('book_authors', [
                    'book_id' => $model->id,
                    'author_id' => $authorId,
                ])->execute();
            }
            
            // Уведомления подписчикам
            $phones = Subscription::find()
                ->select('phone')
                ->where(['author_id' => $model->authorIds])
                ->column();
            foreach ($phones as $phone) {
                $sms = new SmsClient(Yii::$app->params['smsApiKey']);
                $sms->send($phone, "Новая книга: {$model->title}");
            }
            
            Yii::$app->session->setFlash('success', 'Книга создана');
            return $this->redirect(['view', 'id' => $model->id]);
        }
    }
    
    return $this->render('create', [
        'model' => $model,
        'authors' => ArrayHelper::map(Author::find()->all(), 'id', 'fio'),
    ]);
}
```

#### ✅ Плюсы:
- Быстро написать (30 минут)
- Всё в одном месте — легко найти
- Не нужно думать об архитектуре

#### ❌ Минусы:
- **60+ строк** в одном методе
- `actionUpdate` — копипаста с 80% совпадением
- SMS блокирует ответ страницы (100 подписчиков = 30 сек)
- Тесты? Нужен Yii + база + файловая система + SMS API
- Поменял валидацию ISBN — трогаешь контроллер
- Поменял отправку SMS — трогаешь контроллер

---

### Уровень 2: Контроллер + Сервис

```php
// controllers/BookController.php
public function actionCreate()
{
    $model = new Book();
    
    if ($model->load(Yii::$app->request->post()) && $model->validate()) {
        $service = new BookService();
        $bookId = $service->create($model);
        
        if ($bookId) {
            Yii::$app->session->setFlash('success', 'Книга создана');
            return $this->redirect(['view', 'id' => $bookId]);
        }
    }
    
    return $this->render('create', [
        'model' => $model,
        'authors' => ArrayHelper::map(Author::find()->all(), 'id', 'fio'),
    ]);
}
```

```php
// services/BookService.php
class BookService
{
    public function create(Book $model): ?int
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Загрузка файла
            $file = UploadedFile::getInstance($model, 'coverFile');
            if ($file) {
                $path = 'uploads/' . uniqid() . '.' . $file->extension;
                $file->saveAs(Yii::getAlias('@webroot/' . $path));
                $model->cover_url = '/' . $path;
            }
            
            if (!$model->save()) {
                throw new \Exception('Ошибка сохранения');
            }
            
            $this->syncAuthors($model->id, $model->authorIds);
            $transaction->commit();
            
            $this->notifySubscribers($model);
            
            return $model->id;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage());
            return null;
        }
    }
    
    private function syncAuthors(int $bookId, array $authorIds): void
    {
        // ... синхронизация
    }
    
    private function notifySubscribers(Book $model): void
    {
        // ... SMS
    }
}
```

#### ✅ Плюсы:
- Контроллер тонкий (15 строк)
- Логика переиспользуется (Create/Update могут вызывать сервис)
- Легче читать

#### ❌ Минусы:
- Сервис всё ещё **зависит от `Book` (ActiveRecord)**
- Сервис знает про `UploadedFile`, `Yii::$app`
- **Тестирование:** всё ещё нужна вся инфраструктура
- SMS всё ещё блокирует запрос
- Один сервис на 200+ строк (BookService делает ВСЁ)
- Сервис — это "толстый контроллер, вынесенный в класс"

---

### Уровень 3: Clean Architecture (этот проект)

```php
// presentation/controllers/BookController.php
public function actionCreate(): string|Response|array
{
    $form = new BookForm();
    
    if ($this->request->isPost && $form->load($this->request->post())) {
        if ($this->request->isAjax) {
            $this->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($form);
        }
        
        if ($form->validate()) {
            $bookId = $this->commandService->createBook($form);
            if ($bookId !== null) {
                return $this->redirect(['view', 'id' => $bookId]);
            }
        }
    }
    
    return $this->render('create', [
        'model' => $form,
        'authors' => $this->viewService->getAuthorsList(),
    ]);
}
```

```php
// presentation/services/books/BookCommandService.php
public function createBook(BookForm $form): ?int
{
    $coverUrl = $form->coverFile 
        ? $this->fileStorage->save($form->coverFile) 
        : null;
    
    $command = $this->mapper->toCreateCommand($form, $coverUrl);
    
    return $this->executor->execute(
        fn() => $this->createBookUseCase->execute($command),
        successMessage: Yii::t('app', 'Книга успешно создана')
    );
}
```

```php
// application/books/usecases/CreateBookUseCase.php
public function execute(CreateBookCommand $command): int
{
    $this->transaction->begin();
    
    try {
        $bookId = $this->bookRepository->create(
            title: $command->title,
            year: new BookYear($command->year),
            isbn: new Isbn($command->isbn),
            description: $command->description,
            coverUrl: $command->cover
        );
        
        $this->bookRepository->syncAuthors($bookId, $command->authorIds);
        $this->transaction->commit();
        
        $this->eventPublisher->publishEvent(
            new BookCreatedEvent($bookId, $command->title)
        );
        
        return $bookId;
    } catch (\Throwable $e) {
        $this->transaction->rollBack();
        throw $e;
    }
}
```

```php
// domain/values/Isbn.php
final readonly class Isbn
{
    public string $value;
    
    public function __construct(string $isbn)
    {
        $normalized = $this->normalize($isbn);
        if (!$this->isValidChecksum($normalized)) {
            throw new DomainException("Неверный ISBN: {$isbn}");
        }
        $this->value = $normalized;
    }
}
```

#### ✅ Плюсы:
- **UseCase не знает про Yii** — чистый PHP
- **Тестируется изолированно** — mock-аем интерфейсы
- **SMS в очереди** — страница отвечает мгновенно
- **Value Object** — невозможно создать невалидный ISBN
- **Каждый класс = одна ответственность**
- **Легко менять:** новый SMS-провайдер = новый адаптер, UseCase не трогаем

#### ❌ Минусы:
- **Много файлов** (Form + Mapper + Command + UseCase + Repository + Event)
- **Дольше писать** изначально
- **Overkill** для простых CRUD
- **Нужно понимать паттерны**

---

## 📈 Сравнительная таблица

| Критерий | Толстый контроллер | +Сервис | Clean Architecture |
|----------|-------------------|---------|-------------------|
| **Время разработки** | ⚡ 30 мин | ⚡ 1 час | 🐢 3-4 часа |
| **Файлов на операцию** | 1 | 2 | 6-8 |
| **Строк кода** | 60 в одном | 15 + 80 | 15 + 20 + 25 + ... |
| **Unit-тесты** | ❌ Невозможно | ⚠️ Сложно | ✅ Легко |
| **Покрытие тестами** | 0-10% | 10-30% | 80-95% |
| **SMS блокирует** | ✅ Да | ✅ Да | ❌ Нет (очередь) |
| **Зависимость от Yii** | 🔴 Везде | 🟡 В сервисе | 🟢 Infrastructure + Presentation |
| **Изменить провайдера SMS** | Правим контроллер | Правим сервис | Новый адаптер |
| **Копипаста Create/Update** | 80% | 50% | 10% |
| **Onboarding нового дева** | ⚡ 1 день | 2-3 дня | 1 неделя |
| **Поддержка через 2 года** | 😱 Ад | 😐 Норм | 😊 Легко |

---

## 🧩 Каждый паттерн: было → стало

### 1. Form (отдельная валидация)

**Было (в модели Book):**
```php
class Book extends ActiveRecord
{
    public $coverFile;  // Для загрузки
    public $authorIds;  // Для формы
    
    public function rules()
    {
        return [
            // Правила для БД
            ['title', 'string', 'max' => 255],
            // + правила для формы
            ['coverFile', 'file', 'extensions' => 'png, jpg'],
            // + сценарии create/update
        ];
    }
}
```
❌ **Проблема:** Модель смешивает "что хранить" и "что ввёл юзер"

**Стало (BookForm):**
```php
// Только для валидации ввода
class BookForm extends Model
{
    public ?string $title = null;
    public ?UploadedFile $coverFile = null;  // Файл от юзера
    public array $authorIds = [];
}

// ActiveRecord чистый
class Book extends ActiveRecord
{
    // Только поля БД: title, cover_url, year, isbn
}
```
✅ **Результат:** Модель не знает про `UploadedFile`. Форма не знает про БД.

---

### 2. Command (чёткие данные)

**Было:**
```php
$service->create($model);  // Book? BookForm? Array? Хз
```
❌ **Проблема:** Что внутри `$model`? Какие поля есть?

**Стало:**
```php
$command = new CreateBookCommand(
    title: 'Название',
    year: 2024,
    isbn: '9783161484100',
    authorIds: [1, 2],
    cover: '/uploads/cover.jpg'  // Уже URL, не файл!
);
$useCase->execute($command);
```
✅ **Результат:** IDE подсказывает. Типы строгие. Нельзя передать фигню.

---

### 3. Mapper (преобразование)

**Было (в контроллере):**
```php
$command = new CreateBookCommand(
    $form->title,
    $form->year,
    $form->isbn,
    $form->authorIds,
    $coverUrl  // откуда-то взялся
);
```
❌ **Проблема:** Копипаста в каждом контроллере

**Стало:**
```php
// presentation/mappers/BookFormMapper.php
class BookFormMapper
{
    public function toCreateCommand(BookForm $form, ?string $coverUrl): CreateBookCommand
    {
        return new CreateBookCommand(
            title: $form->title,
            year: $form->year,
            isbn: $form->isbn,
            authorIds: $form->authorIds,
            cover: $coverUrl
        );
    }
}
```
✅ **Результат:** Маппинг в одном месте. DRY.

---

### 4. UseCase (бизнес-логика)

**Было (в сервисе):**
```php
class BookService
{
    public function create(Book $model) { /* 100 строк */ }
    public function update(Book $model) { /* 100 строк */ }
    public function delete(int $id) { /* 30 строк */ }
    public function search(string $q) { /* 50 строк */ }
    // ... 500 строк
}
```
❌ **Проблема:** Один файл на 500 строк. God Object.

**Стало:**
```php
// Один файл = одна операция
app/application/books/usecases/
├── CreateBookUseCase.php   // 30 строк
├── UpdateBookUseCase.php   // 25 строк
├── DeleteBookUseCase.php   // 15 строк
```
✅ **Результат:** Маленькие классы. Легко найти и изменить.

---

### 5. Repository (абстракция БД)

**Было:**
```php
// В сервисе
$book = Book::findOne($id);
$book->title = $newTitle;
$book->save();
```
❌ **Проблема:** Сервис зависит от ActiveRecord

**Стало:**
```php
// Интерфейс (application/ports/)
interface BookRepositoryInterface
{
    public function findById(int $id): ?BookReadDto;
    public function create(string $title, BookYear $year, ...): int;
}

// Реализация (infrastructure/repositories/)
class BookRepository implements BookRepositoryInterface
{
    public function create(...): int
    {
        $book = Book::create(...);  // ActiveRecord только тут
        $book->save();
        return $book->id;
    }
}
```
✅ **Результат:** UseCase зависит от интерфейса. В тестах — mock.

---

### 6. Value Object (доменные правила)

**Было:**
```php
// Валидация размазана
// В контроллере:
if (!preg_match('/^\d{13}$/', $isbn)) { ... }
// В модели:
['isbn', 'match', 'pattern' => '/^\d{13}$/']
// И всё равно можно:
$book->isbn = 'фигня';
$book->save();  // Сохранится!
```
❌ **Проблема:** Невалидный ISBN может попасть в БД

**Стало:**
```php
// domain/values/Isbn.php
$isbn = new Isbn('фигня');  // DomainException!
$isbn = new Isbn('9783161484100');  // OK

// В репозитории
public function create(..., Isbn $isbn, ...)
{
    $book->isbn = $isbn->value;  // Гарантированно валидный
}
```
✅ **Результат:** Невозможно создать невалидный ISBN. Точка.

---

### 7. Domain Event (развязка)

**Было:**
```php
// В сервисе после save()
$this->sendSms(...);  // А если SMS упадёт?
$this->sendEmail(...);  // А если email упадёт?
// Книга не сохранится? Или сохранится но без уведомлений?
```
❌ **Проблема:** Создание книги завязано на отправку SMS

**Стало:**
```php
// UseCase
$this->eventPublisher->publishEvent(new BookCreatedEvent($bookId));
// Книга создана. Точка. UseCase не знает про SMS.

// Инфраструктура слушает событие
// BookCreatedEvent → Queue → NotifySubscribersJob → SMS
```
✅ **Результат:** Упал SMS? Книга всё равно создана. SMS повторится из очереди.

---

### 8. Queue (асинхронность)

**Было:**
```php
foreach ($subscribers as $sub) {
    $sms->send($sub->phone, ...);  // 100 SMS = 30 сек
}
// Юзер ждёт...
```
❌ **Проблема:** Страница висит пока шлются SMS

**Стало:**
```php
// Event → одна задача в очередь
Yii::$app->queue->push(new NotifySubscribersJob($bookId));
// Страница отвечает мгновенно

// Воркер в фоне:
// NotifySubscribersJob → 100x NotifySingleSubscriberJob (параллельно)
```
✅ **Результат:** Юзер не ждёт. SMS отправляются фоном. Ретраи автоматические.

---

## 🎯 Когда какой подход

| Ситуация | Рекомендация |
|----------|--------------|
| Прототип за 2 часа | Толстый контроллер |
| Типичный проект (1-2 дева) | Контроллер + Сервис |
| Сложная бизнес-логика | Clean Architecture |
| Нужны тесты | Clean Architecture |
| Интеграции (SMS, Payment, API) | Clean Architecture |
| 3+ разработчика | Clean Architecture |
| Проект на 2+ года | Clean Architecture |

---

## 📁 Структура этого проекта

```
├── application/           # 🧠 Мозг (чистый PHP, БЕЗ Yii)
│   ├── books/
│   │   ├── commands/     # CreateBookCommand, UpdateBookCommand
│   │   ├── queries/      # BookQueryService, BookReadDto
│   │   └── usecases/     # CreateBookUseCase, DeleteBookUseCase
│   └── ports/            # Интерфейсы (контракты)
│
├── domain/               # 💎 Ядро (чистый PHP, БЕЗ Yii)
│   ├── events/           # BookCreatedEvent
│   ├── exceptions/       # DomainException
│   └── values/           # Isbn, BookYear
│
├── infrastructure/       # 🔧 Реализации (ЗАВИСИТ от Yii)
│   ├── adapters/         # YiiEventPublisher, YiiTranslator
│   ├── persistence/      # ActiveRecord: Book, Author
│   ├── repositories/     # BookRepository implements BookRepositoryInterface
│   ├── queue/            # NotifySubscribersJob
│   └── services/         # SmsService, FileStorage
│
└── presentation/         # 🖥 UI (ЗАВИСИТ от Yii: контроллеры, формы, виджеты)
    ├── controllers/      # Тонкие контроллеры (ActiveForm, Response)
    ├── forms/            # BookForm extends yii\base\Model
    ├── mappers/          # BookFormMapper
    ├── services/         # BookCommandService, BookViewService
    └── validators/       # IsbnValidator extends yii\validators\Validator
```

**Независимы от Yii:** `application/` + `domain/` — можно перенести в Symfony/Laravel без изменений.

**Зависят от Yii:** `infrastructure/` + `presentation/` — специфичны для Yii2.
