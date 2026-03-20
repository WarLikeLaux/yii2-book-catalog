# Проблема классического Yii2

[← Назад к оглавлению](../learning.md)

---

Yii2 не заставляет думать об архитектуре. `ActiveRecord` одновременно является и моделью данных, и объектом валидации, и точкой доступа к БД. Для маленьких проектов это удобно. Для проектов, которые живут дольше полугода — ловушка.

## Толстый контроллер

Типичный Yii2-проект начинается так:

```php
public function actionCreate()
{
    $model = new Book();

    if ($model->load(Yii::$app->request->post())) {
        $file = UploadedFile::getInstance($model, 'coverFile');
        if ($file) {
            $path = 'uploads/' . uniqid() . '.' . $file->extension;
            $file->saveAs(Yii::getAlias('@webroot/' . $path));
            $model->cover_url = '/' . $path;
        }

        $isbn = str_replace(['-', ' '], '', $model->isbn);
        if (strlen($isbn) !== 13 || !ctype_digit($isbn)) {
            $model->addError('isbn', 'Неверный ISBN');
        }

        if (!$model->hasErrors() && $model->save()) {
            Yii::$app->db->createCommand()
                ->delete('book_authors', ['book_id' => $model->id])
                ->execute();
            foreach ($model->authorIds as $authorId) {
                Yii::$app->db->createCommand()->insert('book_authors', [
                    'book_id' => $model->id,
                    'author_id' => $authorId,
                ])->execute();
            }

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

60+ строк. Один метод делает: приём запроса, загрузку файла, валидацию ISBN, сохранение в БД, синхронизацию авторов, отправку SMS, редирект. `actionUpdate` — копипаста с 80% совпадением.

Конкретные проблемы:

- **Тестирование невозможно.** Нужен Yii, база, файловая система, SMS API — всё одновременно.
- **SMS блокирует ответ.** 100 подписчиков = 30 секунд ожидания страницы.
- **Изменение одной части ломает другую.** Поменял формат ISBN — правишь контроллер. Сменил SMS-провайдера — правишь контроллер.

## Контроллер + сервис

Следующий шаг — выделить сервис:

```php
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

    return $this->render('create', ['model' => $model]);
}
```

Контроллер стал тонким. Но проблема переехала в `BookService`:

```php
class BookService
{
    public function create(Book $model): ?int
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $file = UploadedFile::getInstance($model, 'coverFile');
            if ($file) { /* ... */ }

            if (!$model->save()) {
                throw new \Exception('Ошибка сохранения');
            }

            $this->syncAuthors($model->id, $model->authorIds);
            $transaction->commit();
            $this->notifySubscribers($model);

            return $model->id;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return null;
        }
    }
}
```

Сервис зависит от `Book` (ActiveRecord), знает про `UploadedFile`, обращается к `Yii::$app->db`. Тестирование по-прежнему требует инфраструктуру. SMS по-прежнему блокирует. Сервис — тот же толстый контроллер, просто в другом файле.

## Корень проблемы

Проблема не в Yii2 как таковом, а в отсутствии границ между слоями:

| Аспект                   | Толстый контроллер | + Сервис          |
|--------------------------|--------------------|--------------------|
| Бизнес-правила           | В контроллере      | В сервисе          |
| Зависимость от фреймворка | Везде             | В сервисе          |
| Unit-тесты               | Невозможно         | Сложно             |
| Покрытие тестами          | 0-10%             | 10-30%             |
| SMS блокирует            | Да                 | Да                 |
| Копипаста Create/Update  | 80%                | 50%                |
| Поддержка через 2 года   | Ад                 | Терпимо            |

ActiveRecord смешивает три ответственности: валидацию ввода, бизнес-правила и работу с БД. Любое изменение в одной ответственности затрагивает остальные.

## Что предлагает Clean Architecture

Разделить код на слои с чёткими границами, где бизнес-логика не знает ни про Yii, ни про БД, ни про HTTP.

Результат для того же создания книги:

| Критерий                 | Классический Yii2 | Clean Architecture           |
|--------------------------|--------------------|-----------------------------|
| Файлов на операцию       | 1-2                | 6-8                         |
| Unit-тесты               | Невозможно         | Легко                       |
| Покрытие тестами          | 0-10%             | 100%                        |
| SMS блокирует            | Да                 | Нет (очередь)               |
| Зависимость от Yii       | Везде              | Infrastructure + Presentation |
| Изменить провайдера SMS  | Правка контроллера | Новый адаптер               |

Больше файлов — это цена. Но каждый файл делает одну вещь и тестируется изолированно.

## Конкретные сценарии: "зачем мне это?"

Абстрактные аргументы не убеждают. Вот конкретные ситуации, знакомые каждому Yii2-разработчику.

### Сценарий 1: смена SMS-провайдера

Провайдер SmsPilot поднял цены. Нужно перейти на Twilio.

**Классический Yii2:** `grep -r "SmsPilot" src/` → SMS-клиент создаётся в 3 контроллерах, 2 сервисах и 1 консольной команде. В каждом месте: другой формат вызова, другая обработка ошибок. Менять 6 файлов, тестировать руками.

**Clean Architecture:** один интерфейс `SmsSenderInterface`, одна реализация `SmsPilotSender`. Написать `TwilioSender`, поменять строчку в `config/container/adapters.php`. Бизнес-код не трогается. Тесты зелёные, потому что Use Case зависит от интерфейса, а не от SmsPilot.

### Сценарий 2: добавить API к существующему Web

Заказчик хочет мобильное приложение. Нужен REST API, который делает то же самое, что Web-формы.

**Классический Yii2:** `BookController::actionCreate()` содержит `$this->render()`, `Yii::$app->session->setFlash()`, `UploadedFile::getInstance()`. Скопировать в `api/BookController`? Дублирование. Вынести в сервис? Сервис всё ещё знает про `UploadedFile`.

**Clean Architecture:** `CreateBookUseCase` уже не зависит от HTTP. Написать API-контроллер, который создаёт `CreateBookCommand` из JSON вместо формы. Один Use Case — два контроллера. Ноль копипасты.

### Сценарий 3: баг в валидации ISBN

QA нашёл: ISBN-10 с буквой X в контрольном разряде не проходит валидацию.

**Классический Yii2:** где валидация ISBN? В `BookController::actionCreate()` строка 17. В `BookController::actionUpdate()` строка 23. В `api/BookController::actionCreate()` строка 12. В `ImportCommand::actionRun()` строка 45. Четыре места, четыре `if (strlen($isbn) !== 13)`. Одно забыл поправить — баг остался.

**Clean Architecture:** `Isbn::__construct()` — одно место. Поправил — работает везде, потому что невалидный ISBN не может существовать как объект. Нельзя забыть, нельзя обойти.

### Сценарий 4: "почему страница грузится 30 секунд?"

100 подписчиков у автора. Опубликовали книгу — страница висит 30 секунд (100 HTTP-запросов к SMS API синхронно).

**Классический Yii2:** SMS отправляются в `foreach` внутри контроллера или сервиса. Чтобы сделать асинхронно — переписывать контроллер, добавлять Job, менять flow.

**Clean Architecture:** сущность записывает `BookStatusChangedEvent`. Репозиторий публикует его после коммита. `EventJobMappingRegistry` превращает в `NotifySubscribersJob`. Queue Worker разбирает в фоне. Страница отдаётся мгновенно. И так с первого дня — асинхронность встроена в архитектуру, а не прикручена потом.

Следующая глава разбирает, как именно устроены эти слои.

---

[Далее: Clean Architecture →](02-clean-architecture.md)
