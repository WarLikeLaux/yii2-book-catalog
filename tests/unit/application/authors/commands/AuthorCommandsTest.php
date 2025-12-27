<?php

declare(strict_types=1);

namespace tests\unit\application\authors\commands;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use Codeception\Test\Unit;

final class AuthorCommandsTest extends Unit
{
    public function testCreateAuthorCommandStoresFio(): void
    {
        $command = new CreateAuthorCommand(fio: 'Иванов Иван Иванович');

        $this->assertSame('Иванов Иван Иванович', $command->fio);
    }

    public function testUpdateAuthorCommandStoresIdAndFio(): void
    {
        $command = new UpdateAuthorCommand(id: 42, fio: 'Петров Петр Петрович');

        $this->assertSame(42, $command->id);
        $this->assertSame('Петров Петр Петрович', $command->fio);
    }

    public function testDeleteAuthorCommandStoresId(): void
    {
        $command = new DeleteAuthorCommand(id: 99);

        $this->assertSame(99, $command->id);
    }
}
