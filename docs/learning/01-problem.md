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

Следующая глава разбирает, как именно устроены эти слои.

---

[Далее: Clean Architecture →](02-clean-architecture.md)
