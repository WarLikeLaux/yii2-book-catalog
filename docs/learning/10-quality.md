# Обеспечение качества

[← Назад к оглавлению](../learning.md)

---

Архитектурные правила, которые держатся на договорённостях, нарушаются при первом дедлайне. В проекте правила проверяются автоматически: нарушение = ошибка сборки.

## Цикл разработки

```bash
make dev          # PHPCS Fixer + Rector + проверка комментариев
make test         # Unit-тесты (PHPUnit)
make analyze      # Lint + Arch + Rector dry-run + PHPStan
```

Каждый из этих шагов должен быть зелёным перед сдачей. `make test-full` — финальная проверка с integration-тестами и coverage.

## PHPStan Level 9

Максимальный уровень строгости. Все типы должны быть явными, `mixed` запрещён без аннотации.

Кроме стандартных правил — 10 кастомных:

| Правило | Что проверяет |
|---------|---------------|
| `UseCaseMustBeFinalRule` | Use Case обязан быть `final` |
| `ValueObjectMustBeFinalRule` | Value Object обязан быть `final` |
| `DomainIsCleanRule` | Domain не импортирует Infrastructure/Presentation |
| `DisallowYiiTOutsideAdaptersRule` | `Yii::$app` только в адаптерах |
| `DisallowDateTimeRule` | `new DateTime()` запрещён, используй `ClockInterface` |
| `NoActiveRecordInDomainOrApplicationRule` | ActiveRecord только в Infrastructure |
| `QueryPortsMustReturnDtoRule` | Query-порты возвращают DTO, не Entity |
| `StrictRepositoryReturnTypeRule` | Repository типизирован строго |
| `DomainEntitiesMustBePureRule` | Entity без инфраструктурных зависимостей |
| `NoGhostQueryServiceInApplicationRule` | Query Services в правильных слоях |

Пример — если кто-то импортирует ActiveRecord в Use Case:

```php
// application/books/usecases/CreateBookUseCase.php
use app\infrastructure\persistence\Book;  // ← PHPStan ERROR
```

Ошибка на CI, не в code review.

## Deptrac — архитектурные границы

```
Presentation → Application → Domain ← Infrastructure
```

Deptrac проверяет, что импорты соответствуют этим правилам. Domain не может импортировать из Infrastructure. Application не может импортировать из Presentation.

Исключение (документировано в `docs/DECISIONS.md`): Presentation может использовать некоторые доменные типы (Value Objects, Enums) напрямую — контролируемое ослабление ради типобезопасности.

## PHPArkitect — архитектурные правила

Дополняет Deptrac правилами на уровне классов:

- Use Case должен быть `final` и `readonly`
- Value Object должен быть `final` и `readonly`
- Entity не должна быть `readonly` (у неё мутабельное состояние)
- Все классы в `domain/` не должны иметь зависимостей от `yii\`

## Rector — автоматический рефакторинг

```bash
make rector       # Dry-run: показывает, что изменит
make rector-fix   # Применяет изменения
```

Rector приводит код к стандартам PHP 8.5: строгие типы, `readonly` где можно, убирает устаревшие конструкции. Запускается в `make dev` автоматически.

## Тестирование

### Unit-тесты (PHPUnit)

850+ тестов. Покрывают Domain и Application без инфраструктуры:

```php
public function testTransitionToPublishedRequiresPolicy(): void
{
    $book = BookTestHelper::createBook();

    $this->expectException(BusinessRuleException::class);
    $book->transitionTo(BookStatus::Published);
}
```

Тест создаёт Entity в памяти, вызывает метод, проверяет результат. Никакой БД, никакого Yii2. Выполняется за миллисекунды.

### Integration-тесты (Codeception)

150+ тестов. Проверяют Infrastructure: репозитории, query services, адаптеры. Работают с реальной БД.

### E2E-тесты (Selenium)

17 тестов. Проверяют полный пользовательский сценарий через браузер.

### Покрытие

100% code coverage для Domain и Application. Измеряется через PCOV.

```bash
make test-full    # Unit + Integration + Coverage report
```

## Mutation Testing (Infection)

Code coverage показывает, что строка выполнялась. Mutation testing показывает, что тесты действительно проверяют логику.

Infection заменяет `>` на `>=`, `true` на `false`, удаляет строки — и проверяет, что тесты падают. Если тест не упал после мутации — тест бесполезен.

MSI (Mutation Score Indicator): 100%. Каждая мутация ловится тестами.

```bash
make infection    # Запуск мутационного тестирования
```

## PHPCS — стиль кода

Проверяет форматирование. Автоисправление в `make dev`:

```bash
make lint         # Проверка
make lint-fix     # Исправление
```

## Проверка комментариев

Комментарии в коде запрещены (правило контракта). `make dev` проверяет наличие `//` и `/* */` в PHP-файлах (кроме технических PHPDoc). Найденный комментарий — ошибка сборки.

## Итого

Качество обеспечивается автоматически на каждом этапе:

| Инструмент | Что проверяет | Когда |
|------------|---------------|-------|
| PHPCS + Rector | Стиль и модернизация | `make dev` |
| PHPStan Level 9 | Типы + 10 архитектурных правил | `make analyze` |
| Deptrac | Зависимости между слоями | `make arch` |
| PHPArkitect | Правила на уровне классов | `make arch` |
| PHPUnit | Unit-тесты Domain/Application | `make test` |
| Codeception | Integration-тесты Infrastructure | `make test-full` |
| Infection | Мутационное тестирование | `make infection` |

Нарушение любого правила — красный CI. Не договорённость, а гарантия.

---

[Далее: Компромиссы и решения →](11-decisions.md)
