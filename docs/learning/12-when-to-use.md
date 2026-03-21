# Когда нужна Clean Architecture, а когда нет

[← Назад к оглавлению](../learning.md)

---

Clean Architecture — не серебряная пуля. Она решает конкретные проблемы и создаёт конкретные затраты. Если проблем нет — затраты не окупаются.

## Когда Clean Architecture оправдана

### Сложная бизнес-логика

Если в проекте есть правила вроде "книгу нельзя опубликовать без обложки, описания минимум 50 символов и хотя бы одного автора, а ISBN можно менять только у черновика" — эти правила заслуживают отдельного слоя.

В классическом Yii2 эти проверки разбросаны: часть в `rules()`, часть в контроллере, часть в `beforeSave()`. Найти все правила для одной операции — квест. В Clean Architecture: открыл `Book::transitionTo()` и `BookPublicationPolicy` — увидел все правила публикации.

**Признак:** если бизнес-правила занимают больше 20% кода контроллера — пора выделять Domain.

### Проект живёт дольше года

Через год разработчик (или ты сам) откроет код и попытается понять, почему SMS отправляются из `BookController::actionCreate()`. С Clean Architecture: `BookStatusChangedEvent → NotifySubscribersJob` — цепочка явная, каждое звено в своём файле.

**Признак:** если в команде больше одного человека или проект передаётся — Clean Architecture как документация.

### Нужны тесты

В классическом Yii2 unit-тест `BookController::actionCreate()` требует: Yii Application, БД, файловую систему, SMS API. Тест выполняется 2-5 секунд и ломается при любом изменении инфраструктуры.

В Clean Architecture: тест `CreateBookUseCase` — создать Entity в памяти, передать мок репозитория, проверить результат. 10 мс. Без БД, без Yii, без файлов.

**Признак:** если без тестов страшно деплоить — Clean Architecture сделает тесты реальными.

### Несколько точек входа

Один Use Case — несколько Presentation:
- Web-контроллер рендерит HTML
- API-контроллер отдаёт JSON
- Console-команда импортирует из CSV
- Queue-обработчик реагирует на событие

Без Clean Architecture бизнес-логика дублируется в каждой точке входа. С ней — один `CreateBookUseCase`, три обёртки.

**Признак:** если появляется API рядом с Web — Clean Architecture предотвращает копипасту.

### Смена инфраструктуры

Проект начинался с MySQL, теперь нужен PostgreSQL. Или SMS-провайдер обанкротился. Или Redis заменяется на Memcached. Или вместо Yii2 Queue нужен RabbitMQ.

В классическом Yii2: `grep -r "Yii::$app->db"` → 47 файлов. В Clean Architecture: заменить одну реализацию интерфейса в `config/container/`.

Этот проект поддерживает MySQL и PostgreSQL одновременно — переключение через `.env`.

**Признак:** если требования к инфраструктуре не зафиксированы на 3 года вперёд — Clean Architecture страхует от смены.

## Когда Clean Architecture избыточна

### CRUD без бизнес-логики

Админка с таблицами: список → создать → редактировать → удалить. Никаких правил, никаких проверок, никаких побочных эффектов.

```php
public function actionCreate()
{
    $model = new Category();
    if ($model->load(Yii::$app->request->post()) && $model->save()) {
        return $this->redirect(['index']);
    }
    return $this->render('create', ['model' => $model]);
}
```

10 строк. Работает. Тестировать нечего — фреймворк проверен за тебя. Добавлять Use Case, Command DTO, Repository, Mapper для `$model->save()` — пустая трата.

**Правило:** если контроллер < 15 строк и не содержит `if` с бизнес-логикой — оставь как есть.

### Прототип / MVP

Нужно проверить гипотезу за неделю. Если гипотеза не подтвердится — код выбрасывается. Clean Architecture замедлит в 3 раза при нулевой отдаче.

**Правило:** если код живёт меньше 3 месяцев и у него один разработчик — классический Yii2 быстрее.

### Маленькая команда, маленький проект

1-2 разработчика, 10 таблиц, нет сложных бизнес-правил. Все знают весь код. Тесты — на уровне smoke tests. Деплой — раз в неделю.

**Правило:** если проект помещается в голову одного человека — Clean Architecture добавляет навигационных затрат больше, чем решает проблем.

### Микросервис с одной задачей

Сервис делает одну вещь: принимает webhook, обрабатывает, отдаёт ответ. 3 файла. Добавлять 4 слоя архитектуры к 3 файлам — абсурд.

## Промежуточный вариант: постепенное выделение

Не обязательно сразу переходить на полную Clean Architecture. Можно двигаться итеративно:

### Шаг 1: выделить Form из ActiveRecord

```php
// Было: Book extends ActiveRecord с rules() и бизнес-логикой
// Стало: BookForm (валидация ввода) + Book (AR, только persistence)
```

Минимальное изменение. Форма отвечает за ввод, AR — за хранение. Уже легче тестировать валидацию.

### Шаг 2: выделить Service

```php
// Было: логика в контроллере
// Стало: BookService::create(BookForm $form) — логика в сервисе
```

Контроллер тонкий. Сервис можно вызвать из разных контроллеров.

### Шаг 3: интерфейсы для внешних зависимостей

```php
// Было: Yii::$app->queue->push(new NotifyJob(...))
// Стало: $this->queue->push(new NotifyJob(...)) через QueueInterface
```

Теперь сервис тестируется с моком очереди.

### Шаг 4: выделить Domain

Когда бизнес-логика в сервисе вырастает — перенести правила в Entity и Value Objects. Сервис становится Use Case.

Этот проект — результат полного прохождения всех четырёх шагов. Но начинать стоит с первого.

## Сколько файлов на операцию

Честное сравнение количества файлов, задействованных в создании книги:

### Классический Yii2: 2 файла

```
controllers/BookController.php     (actionCreate)
views/book/create.php              (форма)
```

### Clean Architecture: 12 файлов

```
presentation/controllers/BookController.php    (координатор)
presentation/books/forms/BookForm.php          (валидация)
presentation/books/handlers/BookCommandHandler.php  (связь Form ↔ UseCase)
presentation/books/mappers/BookCommandMapper.php    (Form → Command)
presentation/books/dto/BookEditViewModel.php        (данные для View)
presentation/books/views/create.php                 (шаблон)
application/books/commands/CreateBookCommand.php    (DTO команды)
application/books/usecases/CreateBookUseCase.php    (бизнес-логика)
domain/entities/Book.php                            (сущность)
domain/values/Isbn.php                              (Value Object)
infrastructure/repositories/BookRepository.php      (persistence)
infrastructure/persistence/Book.php                 (ActiveRecord)
```

12 файлов вместо 2. Но каждый файл — 15-40 строк. Каждый делает одну вещь. Каждый тестируется отдельно. Изменение SMS-провайдера — правка одного адаптера, а не 12 контроллеров.

## Резюме

| Сценарий | Рекомендация |
|----------|-------------|
| CRUD-админка, 10 таблиц | Классический Yii2 |
| MVP / прототип на выброс | Классический Yii2 |
| Проект с бизнес-логикой, живёт > 1 года | Clean Architecture |
| Несколько точек входа (Web + API + CLI) | Clean Architecture |
| Команда > 2 человек | Clean Architecture |
| Нужны быстрые unit-тесты | Clean Architecture |
| Микросервис на 3 файла | Классический Yii2 |
| Не уверен | Начни с шагов 1-2, дойди до полной CA когда потребуется |

---

[← Назад к оглавлению](../learning.md)
