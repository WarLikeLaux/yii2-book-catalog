---
name: migrate
description: Правила и чеклист для создания Yii2 миграций БД
---

# Скилл: миграции Yii2

## Конвенции

### Создание файла

Используй make-команду — она сама генерирует файл с правильным таймштампом:

```bash
make migrate-create name=add_column_comments_to_arts
```

Имя — `snake_case`, описывает суть изменений.

### Структура файла

```php
<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260216_030000_description extends Migration
{
    private const TABLE = 'arts';

    public function safeUp(): void
    {
        $this->addColumn(self::TABLE, 'new_field', $this->string());
    }

    public function safeDown(): void
    {
        $this->dropColumn(self::TABLE, 'new_field');
    }
}
```

**Обязательно:**

- `declare(strict_types=1)`
- `final class`
- `safeUp()` / `safeDown()` (не `up()`/`down()` — нужна транзакция)
- Типизация возвращаемых значений `: void`
- Название таблицы — в `private const TABLE` (или `TABLE_*` при нескольких)
- **Без комментариев** — никаких `//`, `@inheritdoc`, `/* */`
- `safeDown()` должен корректно откатывать ВСЕ изменения из `safeUp()`

## Подводные камни

### 1. Foreign Key Checks

MySQL блокирует `MODIFY`/`ALTER` на колонках, участвующих в FK-ограничениях. Решение:

```php
$this->execute('SET FOREIGN_KEY_CHECKS=0');
// ... ALTER операции ...
$this->execute('SET FOREIGN_KEY_CHECKS=1');
```

**Когда нужно:** любые MODIFY на PK-колонках или колонках с FK-ссылками.

### 2. AUTO_INCREMENT

При `MODIFY` колонки PK обязательно сохранять `AUTO_INCREMENT`:

```php
$autoInc = $colSchema->autoIncrement ? ' AUTO_INCREMENT' : '';
$sql = "ALTER TABLE `{$table}` MODIFY `{$column}` {$type}{$nullable}{$default}{$autoInc} COMMENT ...";
```

Без этого PK потеряет AUTO_INCREMENT и таблица сломается.

### 3. Кодировка

При работе с русскоязычными данными (COMMENT и т.д.) убедись, что соединение использует `utf8mb4`.

### 4. safeDown() для addColumn/dropColumn

```php
// safeUp
$this->addColumn('arts', 'new_field', $this->string());

// safeDown
$this->dropColumn('arts', 'new_field');
```

### 5. safeDown() для createTable/dropTable

```php
// safeUp
$this->createTable('new_table', [...]);

// safeDown
$this->dropTable('new_table');
```

### 6. Индексы и FK

Создавай индексы по фактическим запросам, не "на всякий случай":

```php
$this->createIndex('idx-arts-status', 'arts', 'status');
$this->addForeignKey('fk-arts-brand_id', 'arts', 'brand_id', 'brands', 'id', 'CASCADE');
```

В `safeDown()` удаляй в обратном порядке: сначала FK, потом индекс.

### 7. Проверка существования колонки

Если не уверен, что колонка существует:

```php
$schema = $this->db->getTableSchema('table_name');
if ($schema?->getColumn('column_name') === null) {
    return; // или skip
}
```

## Чеклист перед запуском

- [ ] `safeDown()` корректно откатывает все изменения
- [ ] `FOREIGN_KEY_CHECKS` отключены при MODIFY на FK/PK
- [ ] `AUTO_INCREMENT` сохранён при MODIFY на PK
- [ ] Имя файла соответствует конвенции
- [ ] Файл в `console/migrations/`
- [ ] `final class`, типизация

## Запуск

```bash
make migrate        # Применить миграции
make migrate-down   # Откатить последнюю
```

## Проверка результата

```bash
make db-describe table=TABLE_NAME  # Структура + комментарии
make db-schema table=TABLE_NAME    # Полный CREATE TABLE
make db-indexes table=TABLE_NAME   # Индексы
```
