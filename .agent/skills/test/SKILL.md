---
name: test
description: Правила и конвенции написания тестов Codeception для Yii2
---

# Скилл: TEST

## ⛔️ КРИТИЧЕСКИ ВАЖНО

**В Functional-тестах JavaScript НЕ РАБОТАЕТ.** Codeception использует внутренний PHP-браузер, а не реальный. Это значит:

- ❌ AJAX-запросы не выполняются
- ❌ Модальные окна не открываются
- ❌ Динамический контент (jQuery, Vue, React) не рендерится
- ❌ `$(document).ready()`, `fetch()`, `XMLHttpRequest` — ничего из этого
- ❌ Элементы, которые появляются через JS (табы, аккордеоны, autocomplete) — невидимы

**Тестируй только серверный HTML-ответ** — то, что отдаёт PHP до выполнения JS.

## Обзор

Проект использует **Codeception** (Yii2 модуль). Два типа тестов:

- **Functional (Cest)** — HTTP-тесты фронтенда/бэкенда через `FunctionalTester`
- **Unit** — юнит-тесты моделей, сервисов, трейтов через `UnitTester`

E2E (Selenium/Playwright) — НЕ используется.

## 1. Определи тип теста

| Что тестируешь | Тип | Где |
|----------------|-----|-----|
| HTTP-ответ, наличие элементов, формы, навигация | Functional (Cest) | `frontend/tests/functional/` или `backend/tests/functional/` |
| Модель, сервис, трейт, хелпер, бизнес-логика | Unit | `common/tests/unit/` |

## 2. Создай файл теста

### Functional (Cest)

```bash
# Создание через Codeception
make shell
cd frontend && vendor/bin/codecept generate:cest functional FeatureName
```

Или вручную — файл `frontend/tests/functional/FeatureNameCest.php`:

```php
<?php

declare(strict_types=1);

namespace frontend\tests\functional;

use frontend\tests\FunctionalTester;

final class FeatureNameCest
{
    public function checkSomething(FunctionalTester $I): void
    {
        $I->amOnRoute('/route');
        $I->seeResponseCodeIs(200);
        $I->see('Заголовок', 'h1');
        $I->seeElement('.css-selector');
    }
}
```

### Unit
### Unit

Файл `common/tests/unit/models/ArtsTest.php`:

```php
<?php

declare(strict_types=1);

namespace common\tests\unit\models;

use Codeception\Test\Unit;
use common\models\Arts;

final class ArtsTest extends Unit
{
    public function testValidation(): void
    {
        $model = new Arts();
        $model->name = '';

        $this->assertFalse($model->validate(['name']));
    }
}
```

## 3. Конвенции

### Именование

- Functional: `{Feature}Cest.php`, методы — `check{What}`, `verify{What}`
- Unit: `{Class}Test.php`, методы — `test{Behavior}`

### Структура метода

Паттерн **Arrange → Act → Assert**, без комментариев-разделителей:

```php
public function testCreateProduct(): void
{
    $service = new ProductService($this->repository);

    $product = $service->create('Test Product');

    $this->assertNotNull($product->id);
    $this->assertSame('Test Product', $product->name);
}
```

### Правила контракта

- `declare(strict_types=1)`
- `final class`
- Без комментариев (`//`, `/* */`, `@inheritdoc`)
- Типизация возвращаемых значений `: void`
- Один тест = одно поведение

## 4. Запуск

```bash
make test           # Все тесты
make test/home      # Только HomeCest
make test/type      # Только TypeCest
make test/search    # Только SearchCest
```

## 5. Полезные методы FunctionalTester

### Навигация

```php
$I->amOnRoute('/product/view', ['id' => 1]);
$I->amOnPage('/brands');
$I->click('Каталог');
```

### Проверка контента

```php
$I->see('Текст', 'h1');
$I->dontSee('Ошибка');
$I->seeElement('.product-card');
$I->seeLink('Бренды', '/brands');
$I->seeResponseCodeIs(200);
```

### Подсчёт элементов

```php
$I->assertCount(7, $I->grabMultiple('.article'));
$I->assertGreaterThan(5, $I->grabMultiple('.product-card'));
```

### URL

```php
$I->assertStringEndsWith('/brands', $I->grabFromCurrentUrl());
```

## ЗАПРЕТЫ

- ⛔️ НЕ пиши E2E/Selenium тесты — не поддерживается в проекте.
- ⛔️ НЕ используй PHPUnit напрямую — только через Codeception.
- ⛔️ НЕ хардкодь ID записей из БД — используй fixtures или динамические данные.
- ⛔️ НЕ тестируй приватные методы — тестируй публичный контракт.
