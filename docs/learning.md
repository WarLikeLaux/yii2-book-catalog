# Изучение проекта

[← Назад в README](../README.md)

---

Пошаговое руководство по архитектуре yii2-book-catalog: от проблем классического Yii2 до Clean Architecture с DDD-элементами.

## Начни отсюда

- **[Глоссарий: термины и аналогии из Yii2](learning/00-glossary.md)**
  Все термины с определениями и аналогиями из привычного Yii2. Открой, если встретишь незнакомое слово.

## Оглавление

1. **[Проблема классического Yii2](learning/01-problem.md)**
   Толстый контроллер, контроллер + сервис, почему оба подхода упираются в стену. Конкретные сценарии: смена SMS-провайдера, добавление API, баг в валидации.

2. **[Clean Architecture за 5 минут](learning/02-clean-architecture.md)**
   Правило зависимостей, четыре слоя, карта директорий проекта.

3. **[Domain layer — бизнес без фреймворка](learning/03-domain.md)**
   Entity как Rich Model, Value Objects, Domain Events, Specification Pattern. С мостиками "если ты привык к Yii2".

4. **[Application layer — оркестрация](learning/04-application.md)**
   Use Cases, Command/Query DTO, Ports, Pipeline и Middleware. Почему `BookService` — не решение.

5. **[Infrastructure layer — адаптеры к реальному миру](learning/05-infrastructure.md)**
   От ActiveRecord к Repository: зачем этот путь. Hydrator, Query Services, Visitor, декораторы.

6. **[Presentation layer — тонкий контроллер](learning/06-presentation.md)**
   ViewModel, Form → Mapper → Command, Idempotency, REST API, HTMX.

7. **[Поток данных: жизнь HTTP-запроса](learning/07-request-lifecycle.md)**
   Сквозной пример создания книги от POST до БД.

8. **[Dependency Injection без магии](learning/08-dependency-injection.md)**
   Конструкторная инъекция, обёртки Yii2-компонентов, DI в очередях.

9. **[Async: события и очереди](learning/09-async.md)**
   Domain Events → Queue → Fan-out → SMS. Dual Write компромисс.

10. **[Обеспечение качества](learning/10-quality.md)**
    PHPStan Level 9, Deptrac, Infection, 10 кастомных правил, цикл `make dev`.

11. **[Компромиссы и осознанные решения](learning/11-decisions.md)**
    Integer ID, Reflection, ActiveRecord в репозиториях, валидация без фанатизма.

12. **[Когда нужна Clean Architecture, а когда нет](learning/12-when-to-use.md)**
    CRUD-админка vs сложная бизнес-логика. Постепенный переход за 4 шага. Честное сравнение: 2 файла vs 12.

## Связанная документация

- [Сравнение подходов (было → стало)](COMPARISON.md) — 19 паттернов с примерами кода
- [Архитектура проекта](ARCHITECTURE.md) — C4-диаграммы, структура модулей
- [Архитектурные решения](DECISIONS.md) — полный список компромиссов
