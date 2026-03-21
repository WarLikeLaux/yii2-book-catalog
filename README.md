<div align="center">
<img src="docs/assets/hero-banner.png" alt="Yii2 Book Catalog: DDD, CQS, Clean Architecture" width="800">

# Yii2 Book Catalog

**Enterprise-grade Clean Architecture on Yii2 Framework • PHP 8.5**

[![PHP Version](https://img.shields.io/badge/PHP-8.5-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Yii2](https://img.shields.io/badge/Yii2-Framework-blue?style=for-the-badge&logo=yii&logoColor=white)](https://www.yiiframework.com/)
[![MySQL](https://img.shields.io/badge/MySQL_/_PgSQL-Multi_DB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/Tests-1009_passed-success?style=for-the-badge&logo=codecov&logoColor=white)](#-тестирование-и-покрытие-кода)
[![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen?style=for-the-badge&logo=codecov&logoColor=white)](#-тестирование-и-покрытие-кода)
[![Mutation Score](https://img.shields.io/badge/MSI-100%25-brightgreen?style=for-the-badge&logo=probot&logoColor=white)](#-тестирование-и-покрытие-кода)

---

<p align="center">
  <b>🏛 Clean Architecture</b> • <b>⚡ CQS Pattern</b> • <b>📨 Domain Events</b><br>
  <b>🎯 Value Objects</b> • <b>🔄 Async Fan-out</b> • <b>🚦 Status FSM</b>
</p>

</div>

---

Этот проект - практическая реализация каталога книг на базе **Yii2** и **PHP 8.5**. Используется **Clean Architecture**, демонстрирующая возможности написания масштабируемого кода на классическом фреймворке.

Принято считать, что AI-агенты лучше «дружат» с Python или Laravel. Этот проект показал обратное - классический **Yii2** тоже позволяет внедрять современные инженерные стандарты. На «олдскульном» фреймворке можно работать качественно и актуально, используя нейросети как инструмент.

Главная цель - **отделить бизнес-логику от фреймворка**, добавить строгую типизацию и сделать асинхронные процессы надежными. **Yii2** в полной мере используется для веба и базы данных, но **Use Cases** и **порты** удерживаются в изоляции. Так код получается чистым, понятным и на 100% покрытым тестами.

📋 Подробная история изменений доступна в [CHANGELOG.md](CHANGELOG.md).

🏗 **Архитектурные решения и диаграммы** - [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)

🆚 **Сравнение подходов (Yii2 MVC и Clean Architecture)** - [docs/COMPARISON.md](docs/COMPARISON.md)

📖 **Изучение проекта** - пошаговое руководство от проблем классического Yii2 до Clean Architecture [docs/learning.md](docs/learning.md)

🧾 **Автособираемая документация** - актуальные отчеты и сводки по коду [docs/generated](docs/generated)

> **🤯 Зачем так сложно?**
>
> Этот проект - **Architectural Showcase**. Он показывает, что **Yii2** может быть и «быстрым стартом» для новичков, и фундаментом для серьезных систем. Главное - правильно его «приготовить», добавив **Clean Architecture**, **DDD** и **SOLID**.
>
> Здесь всё серьезно. **Deptrac** и **Arkitect** следят, чтобы слои не перемешивались, **Infection** проверяет качество тестов, а **PHPStan** на максималках (Level 9 + 10 кастомных правил) ловит даже малейшие неточности.
>
> **🦖 Зачем взял этого "динозавра" (Yii2)?**
>
> Многие считают Yii2 устаревшим, но проект показывает крутой симбиоз - проверенное временем ядро плюс возможности **PHP 8.5** (Enums, Readonly, Attributes).
> «Магия» глобальных переменных полностью убрана, управлением занимается **Dependency Injection**. Код честный - если классу что-то нужно, он запрашивает это в конструкторе.
>
> **🧠 Это просто генерация от AI?**
>
> Нет. Немаловажную часть кода написали автономные AI-агенты, работающие прямо в терминале под полным контролем. Они используются как мощный «экзоскелет» - агенты пишут код по строгим правилам и контрактам, с проверкой и направлением каждого решения. Это пример того, как Senior + AI могут строить качественную архитектуру в разы быстрее, сохраняя контроль над качеством.
>
> **🤔 А есть подвох?**
>
> **Без него никак.** Добавлен **здоровый прагматизм**, чтобы проект не стал «академически правильным, но бесполезным в жизни».
>
> `ActiveRecord` сохранен в инфраструктуре, кастомные решения для БД не создавались. Где фреймворк реально экономит время (формы, роутинг, запросы к БД) - он используется.
>
> Но есть важное правило - фреймворк заперт внутри инфраструктуры. Он инструмент, а не основа. Бизнес-логика вообще не знает о его существовании. Подробнее про эти решения и осознанные компромиссы - [docs/DECISIONS.md](docs/DECISIONS.md)

---

## 📌 Навигация

- [✨ Ключевые особенности](#-ключевые-особенности)
- [🆚 Сравнение подходов](#-сравнение-подходов)
- [🚀 Установка и запуск](#-установка-и-запуск)
- [⚙️ Конфигурация](#️-конфигурация)
- [🛠 Технический стек](#-технический-стек)
- [🧪 Тестирование](#-тестирование-и-покрытие-кода)
- [📂 Структура проекта](#-структура-проекта)

---

## ✨ Ключевые особенности

| 🏛️ Архитектура                                                                   | ⚡ Производительность                                               |
| :------------------------------------------------------------------------------- | :------------------------------------------------------------------ |
| 🔹 **Clean Architecture**<br>Компромисс между чистотой и прагматизмом            | 🚀 **Async Fan-out**<br>Паттерн для масштабируемых уведомлений      |
| 🔹 **Паттерн CQS**<br>Разделение команд и запросов                               | 🔍 **Гибридный поиск**<br>FullText + откат к LIKE                   |
| 🔹 **Value Objects**<br>`Isbn`, `BookYear`, `BookStatus`, `StoredFileReference`  | 🛡 **Idempotency + Mutex**<br>Защита от дублей без гонок            |
| 🔹 **🚦 Status FSM**<br>Конечный автомат статусов книги                          | ⚡ **HTMX**<br>Infinite Scroll и реактивные формы                   |
| 🔹 **📦 CAS (File Storage)**<br>Контентно-адресуемое хранилище                   | 📈 **Observability**<br>Structured Logging + Inspector APM          |
| 🔹 **Доменные события**<br>`BookStatusChangedEvent`, `BookUpdatedEvent`, fan-out | 🩺 **Эндпоинт здоровья (Health Check)**<br>Prod-readiness `/health` |
| **🧪 Качество кода**                                                             | **🐳 DevOps Ready**                                                 |
| ✅ **1009+ тестов** (2479+ assertions)<br>100% покрытие кода тестами              | 🐳 **Docker Compose**<br>Полный стек одной командой                 |
| ✅ **PHPStan Level 9**<br>10 кастомных правил (Custom Rules)                     | 🛠 **Makefile**<br>Автоматизация рутины                             |
| ✅ **Мутационное тестирование**<br>Infection PHP (MSI 100%)                      | 📚 **Генерация документации**<br>Yii2 API + OpenAPI                 |
| ✅ **Авто-рефакторинг**<br>Rector                                                | 🏗 **Контроль архитектуры**<br>Deptrac + Arkitect                   |

[↑ К навигации](#-навигация)

## 🆚 Сравнение подходов

| Критерий          | Yii2 MVC                   | Yii2 MVC + сервисы          | Clean Architecture            |
| :---------------- | :------------------------- | :-------------------------- | :---------------------------- |
| **Бизнес-логика** | Контроллеры и ActiveRecord | Сервисы поверх ActiveRecord | Use Cases и доменные сущности |
| **Зависимости**   | `Yii::$app`                | `Yii::$app`                 | Порты и DI                    |
| **Тестируемость** | Сложно                     | Сложно                      | Легко                         |

Подробное сравнение: [docs/COMPARISON.md](docs/COMPARISON.md)

[↑ К навигации](#-навигация)

## 🚀 Установка и запуск

### ⚡ Быстрый старт (3 команды)

```bash
# 1. Клонируем проект
git clone https://github.com/WarLikeLaux/yii2-book-catalog.git
cd yii2-book-catalog

# 2. Установка (интерактивно)
make install
# или принудительно (без вопросов):
make install-force

# 3. Готово! 🎉
open http://localhost:8000
```

> 💡 **Что делает `make install`:**
>
> - 🐳 Поднимает Docker контейнеры (PHP 8.5 + MySQL 8 / PgSQL + воркер очереди)
> - 📦 Устанавливает Composer зависимости
> - 🗄 Применяет миграции БД
> - 🌱 Наполняет базу демо-данными

<details>
<summary><b>🔑 Тестовые учётные данные</b></summary>

| Логин   | Пароль  |
| ------- | ------- |
| `admin` | `admin` |
| `demo`  | `demo`  |

</details>

Приложение будет доступно по адресу: **[http://localhost:8000](http://localhost:8000)**

### 🛠 Команды

Весь проект управляется через `Makefile`. Запустите `make help`, чтобы увидеть полный список команд (более 30 утилит).

**Самые важные:**

```bash
make install    # полная установка и запуск 🚀
make test       # запуск основных тестов (Unit + Integration) 🧪
make dev        # авто-фикс стиля и статический анализ 🛡️
make up         # управление контейнерами (up/down) 🐳
```

[↑ К навигации](#-навигация)

## ⚙️ Конфигурация

Все настройки приложения вынесены в файл `.env`. При установке (`make install`) он создается автоматически из `.env.example`.

### 🌍 Основные настройки

| Переменная    | Значение по умолчанию | Описание                                        |
| :------------ | :-------------------- | :---------------------------------------------- |
| `YII_ENV`     | `dev`                 | Окружение: `dev`, `prod`, `test`                |
| `YII_DEBUG`   | `true`                | Включить режим отладки Yii2                     |
| `SMS_API_KEY` | `MOCK_KEY`            | Ключ для SMS шлюза. `MOCK_KEY` пишет SMS в лог. |

### 🗄️ База данных

В проекте реализована **горячая смена драйвера БД**.

| Переменная          | Значение          | Описание                                 |
| :------------------ | :---------------- | :--------------------------------------- |
| `DB_DRIVER`         | `mysql` / `pgsql` | Выбор активной базы данных               |
| `DB_NAME`           | `yii2basic`       | Имя базы данных                          |
| `DB_USER`           | `yii2`            | Пользователь БД                          |
| `DB_PASSWORD`       | `secret`          | Пароль пользователя                      |
| `MYSQL_PUBLIC_PORT` | `33060`           | Внешний порт MySQL (доступ с хоста)      |
| `PGSQL_PUBLIC_PORT` | `54320`           | Внешний порт PostgreSQL (доступ с хоста) |

### 🔍 Инфраструктура и отладка

| Переменная          | Порт    | Сервис                                      |
| :------------------ | :------ | :------------------------------------------ |
| `APP_PORT`          | `8000`  | Основной веб-сервер приложения              |
| `SWAGGER_PORT`      | `8081`  | Swagger UI документация                     |
| `REDIS_PUBLIC_PORT` | `6379`  | Внешний доступ к Redis                      |

> 💡 **Совет:** Если порты заняты, просто измените их в `.env` и перезапустите контейнеры через `make up`.

### 🎛️ Интерактивная настройка

Если вы не хотите править файлы вручную, запустите мастер настройки:

```bash
make env   # или make configure
```

Скрипт пошагово спросит все параметры (порты, пароли, драйвер БД) и обновит `.env`. Это полезно, если на вашем компьютере уже занят порт 8000 или 3306.

[↑ К навигации](#-навигация)

## 🛠 Технический стек

| Категория           | Технология                                                                                                           | Описание                                             |
| ------------------- | -------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| **Язык**            | [![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)                 | PHPStan Level 9, Strict Types, Constructor Promotion |
| **Фреймворк**       | [![Yii2](https://img.shields.io/badge/Yii-2.0-blue?logo=yii)](https://www.yiiframework.com/)                         | Basic Template с DI Container                        |
| **База(-ы) данных** | [![MySQL](https://img.shields.io/badge/MySQL_/_PgSQL-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)     | InnoDB / PostgreSQL 16 + FullText Search             |
| **Очереди**         | `yii2-queue`                                                                                                         | DB Driver + Fan-out Pattern                          |
| **Тестирование**    | [![Codeception](https://img.shields.io/badge/Codeception-5.0-purple)](https://codeception.com/)                      | Unit + Integration + E2E, 100% Coverage              |
| **Инфраструктура**  | [![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](https://www.docker.com/) | PHP 8.5 + MySQL 8 / PgSQL 16 + Redis + очередь       |
| **UX / Front**      | [![HTMX](https://img.shields.io/badge/HTMX-2.0-blue)](https://htmx.org/)                                             | Infinite Scroll, Reactive Forms, Modal dialogs       |
| **Качество**        | `Rector`, `PHPStan`, `Deptrac`, `Arkitect`                                                                           | Strict Static Analysis & Architectural Guardrails    |

[↑ К навигации](#-навигация)

### 🧪 Тестирование и покрытие кода

Проект демонстрирует, как **Clean Architecture** превращает тестирование из "рутины" в мощный инструмент быстрой разработки.

#### 🎯 Почему тестов так много и они такие быстрые?

Бизнес-логика (Use Cases) полностью отделена от фреймворка и БД - тестируется «чистый PHP», что позволяет выполнять сотни Unit-тестов за миллисекунды. Все зависимости внедряются через конструктор, и тяжелые части (БД, очереди, SMS-шлюзы) легко подменяются на **Mocks** или **In-memory** реализации. Отсутствие обращений к `Yii::$app` в доменном слое делает тесты изолированными и стабильными.

<table>
<tr>
<td align="center"><b>1009</b><br>Tests</td>
<td align="center"><b>2479+</b><br>Assertions</td>
<td align="center"><b>100%</b><br>Coverage</td>
<td align="center"><b>~13s</b><br>Runtime</td>
</tr>
</table>

Отдельный прогон E2E: 17 сценариев (`make test-e2e`).

#### 🏗 Пирамида тестирования

1.  Unit тесты покрывают доменные сущности, Value Objects и Use Cases в полной изоляции.
    ```bash
    make test-unit
    ```
2.  Integration тесты проверяют взаимодействие слоев - работу репозиториев с реальной БД (MySQL/PgSQL), маппинг событий в очереди и интеграцию с Yii2 компонентами.
    ```bash
    make test-integration
    ```
3.  E2E тесты (17 сценариев) имитируют действия пользователя в браузере - создание книги, публикация, подписка. Прогон выполняется отдельно.
    ```bash
    make test-e2e
    ```

#### 🦠 Мутационное тестирование (Infection)

Помимо покрытия строк (Line Coverage) выполняется **Infection Testing** - «тестирование тестов». Infection вносит ошибки в исходный код (мутанты) и проверяет, "заметят" ли это тесты. **Mutation Code Coverage 100%** и **MSI (Mutation Score Indicator) 100%** означают, что большая часть изменений логики не пройдет незамеченной.

```bash
make test-infection
```

[↑ К навигации](#-навигация)

## 📁 Структура проекта

```text
src/domain/             - Слой домена (Business Logic)
  ├── common/           - Общие доменные элементы
  ├── entities/         - Сущности (Rich Model)
  ├── events/           - Domain Events
  ├── exceptions/       - Исключения домена
  ├── repositories/    
  ├── services/         - Domain Services (редко)
  ├── specifications/   - Specifications (criteria)
  ├── values/           - Value Objects (Immutable)
src/application/        - Слой приложения (Application Logic)
  ├── common/           - Общие DTO и валидаторы
  ├── ports/            - Интерфейсы (Ports)
  ├── {{module}}/      
  │   ├── commands/     - DTO команд (Write)
  │   ├── exceptions/   - Исключения модуля
  │   ├── factories/    - Фабрики модуля
  │   ├── mappers/      - Mappers модуля
  │   ├── queries/      - DTO чтения (Read), DTO-only: final readonly, без infra
  │   ├── usecases/     - Классы Use Case (execute)
src/infrastructure/     - Инфраструктурный слой (Framework Logic)
  ├── adapters/         - Адаптеры инфраструктуры
  ├── components/       - Вспомогательные компоненты
  ├── factories/        - Фабрики инфраструктуры
  ├── listeners/        - Event Listeners
  ├── mapping/          - Настройки маппинга
  ├── persistence/      - ActiveRecord модели (Mapping)
  ├── phpstan/          - Расширения и правила PHPStan
  ├── queries/          - Query Services
  ├── queue/            - Обработчики очередей
  ├── repositories/     - Реализации Repository (через AR)
  ├── services/         - Внешние сервисы
src/presentation/       - Слой представления (UI/API)
  ├── common/           - Общие компоненты
  ├── components/       - UI компоненты
  ├── controllers/      - Общие контроллеры
  ├── dto/              - DTO уровня представления
  ├── mail/             - Шаблоны писем
  ├── services/         - Общие сервисы представления
  ├── views/            - Шаблоны представлений
  ├── widgets/          - UI виджеты
  ├── {{module}}/      
  │   ├── dto/          - DTO уровня представления
  │   ├── forms/        - Формы валидации
  │   ├── handlers/     - Обработчики запросов
  │   ├── mappers/      - Mappers модуля
  │   ├── services/     - Сервисы модуля
  │   ├── validators/   - Валидаторы модуля
  │   ├── widgets/      - Виджеты модуля
assets/                 - Frontend assets
bin/                    - CLI утилиты
  ├── lib/              - Библиотеки CLI утилит
commands/               - Console контроллеры
  ├── support/          - Служебные утилиты и вывод карты проекта
config/                 - Конфигурация приложения
  ├── container/        - Конфигурация контейнера зависимостей
docker/                 - Docker конфигурация
  ├── nginx/            - Конфигурация nginx
docs/                   - Документация
  ├── ai/               - Правила и инструкции для AI
  ├── generated/        - Автоматизированные материалы
messages/               - Переводы i18n
migrations/             - Миграции БД
runtime/                - Runtime кэш и логи
tests/                  - Тесты
tools/                  - Инструменты разработки
  ├── PHPUnit/          - Конфигурация PHPUnit
  ├── Rector/           - Конфигурация Rector
web/                    - Web root
```

Список модулей:

| Модуль        | Назначение              |
| ------------- | ----------------------- |
| auth          | Авторизация и сессии    |
| authors       | Управление авторами     |
| books         | Каталог книг            |
| reports       | Аналитические отчеты    |
| subscriptions | Подписки на уведомления |

**Независимы от Yii:** `application/` и `domain/`.

**Зависят от Yii:** `infrastructure/` и `presentation/`.

---

<div align="center">

### 📊 Статистика проекта

![Source Code](https://img.shields.io/badge/Source_Code-11k+-blue?style=for-the-badge&logo=icloud&logoColor=white)
![Test Code](https://img.shields.io/badge/Test_Code-17.9k+-blue?style=for-the-badge&logo=codecov&logoColor=white)
![Source Files](https://img.shields.io/badge/Source_Files-335-purple?style=for-the-badge&logo=php&logoColor=white)
![Test Files](https://img.shields.io/badge/Test_Files-223-orange?style=for-the-badge&logo=codecov&logoColor=white)
![Test Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen?style=for-the-badge&logo=codecov&logoColor=white)
![Mutation Score](https://img.shields.io/badge/MSI-100%25-brightgreen?style=for-the-badge&logo=probot&logoColor=white)
![PHPStan](https://img.shields.io/badge/PHPStan-Level_9_+_Strict-brightgreen?style=for-the-badge&logo=probot&logoColor=white)

<br>

**Made with ❤️ using [Yii2 Framework](https://www.yiiframework.com/)**

_Clean Architecture • DDD • CQS • Event-Driven_

</div>
