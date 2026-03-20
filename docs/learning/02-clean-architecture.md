# Clean Architecture за 5 минут

[← Назад к оглавлению](../learning.md)

---

## Одно правило

Зависимости направлены внутрь. Внутренние слои не знают о внешних.

```
Presentation → Application → Domain ← Infrastructure
```

- **Domain** — бизнес-правила. Чистый PHP, без зависимостей.
- **Application** — координация. Чистый PHP, знает только Domain.
- **Infrastructure** — реализация. Yii2, БД, Redis, SMS.
- **Presentation** — HTTP. Контроллеры, формы, views.

Infrastructure зависит от Domain (реализует его интерфейсы), но Domain об Infrastructure не знает.

## Карта директорий проекта

```
src/
├── domain/                 # Бизнес-логика (чистый PHP)
│   ├── entities/           # Сущности (Book, Author, Subscription)
│   ├── values/             # Value Objects (Isbn, BookYear, Phone)
│   ├── events/             # Domain Events
│   ├── exceptions/         # Исключения домена
│   ├── repositories/       # Интерфейсы репозиториев
│   ├── services/           # Domain Services (редко)
│   ├── specifications/     # Спецификации поиска
│   └── common/             # Общие интерфейсы
│
├── application/            # Оркестрация (чистый PHP)
│   ├── books/
│   │   ├── commands/       # DTO команд (CreateBookCommand)
│   │   ├── queries/        # DTO чтения (BookReadDto)
│   │   └── usecases/       # Use Cases (CreateBookUseCase)
│   ├── ports/              # Интерфейсы к внешнему миру (40+)
│   └── common/             # Pipeline, Middleware, Config
│
├── infrastructure/         # Реализация (Yii2)
│   ├── adapters/           # Адаптеры Yii2 (Cache, Queue, Transaction)
│   ├── repositories/       # Реализации репозиториев через AR
│   ├── queries/            # Query Services (read-only)
│   ├── persistence/        # ActiveRecord модели
│   └── services/           # SMS, Storage, Health Checks
│
└── presentation/           # HTTP (Yii2)
    ├── controllers/        # Контроллеры (тонкие)
    ├── books/
    │   ├── forms/          # Формы валидации
    │   ├── handlers/       # Command Handlers
    │   ├── mappers/        # Form → Command маппинг
    │   └── dto/            # ViewModels
    └── views/              # Шаблоны
```

## Что от чего изолировано

**Domain и Application** — независимы от Yii2. Их можно перенести в Symfony или Laravel без изменений. Они не знают про ActiveRecord, HTTP, сессии, очереди.

**Infrastructure** — знает про Yii2 и реализует интерфейсы из Domain/Application. Репозиторий реализует `BookRepositoryInterface` через ActiveRecord. Адаптер реализует `TransactionInterface` через `Yii::$app->db`.

**Presentation** — знает про Yii2 (контроллеры, формы, views) и вызывает Application через Use Cases.

## Зачем такое разделение

Тестируемость. Domain и Application тестируются за миллисекунды — без БД, без HTTP, без фреймворка. В проекте 1008+ тестов, 100% покрытие.

Заменяемость. Сменить SMS-провайдера — написать новый адаптер, подключить в DI. Перейти с MySQL на PostgreSQL — поменять конфиг (проект поддерживает оба). Бизнес-логика не меняется.

Читаемость. Каждый файл делает одну вещь. `CreateBookUseCase` — создаёт книгу. `Isbn` — валидирует ISBN. `BookRepository` — сохраняет в БД.

## Инструменты контроля

Разделение слоёв проверяется автоматически:

- **Deptrac** — запрещает импорты между слоями (например, Domain не может импортировать из Infrastructure)
- **PHPArkitect** — архитектурные правила (UseCase должен быть `final`)
- **PHPStan** — 10 кастомных правил (нет ActiveRecord в Domain, нет `Yii::$app` вне адаптеров)

Нарушение границы — ошибка сборки, а не договорённость в команде.

---

[Далее: Domain layer →](03-domain.md)
