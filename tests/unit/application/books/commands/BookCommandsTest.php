<?php

declare(strict_types=1);

namespace tests\unit\application\books\commands;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\DeleteBookCommand;
use app\application\books\commands\UpdateBookCommand;
use Codeception\Test\Unit;

final class BookCommandsTest extends Unit
{
    public function testCreateBookCommandStoresAllProperties(): void
    {
        $command = new CreateBookCommand(
            title: 'Clean Code',
            year: 2008,
            description: 'A book about clean code',
            isbn: '9780132350884',
            authorIds: [1, 2, 3],
            cover: '/uploads/cover.jpg'
        );

        $this->assertSame('Clean Code', $command->title);
        $this->assertSame(2008, $command->year);
        $this->assertSame('A book about clean code', $command->description);
        $this->assertSame('9780132350884', $command->isbn);
        $this->assertSame([1, 2, 3], $command->authorIds);
        $this->assertSame('/uploads/cover.jpg', $command->cover);
    }

    public function testCreateBookCommandWithDefaultCover(): void
    {
        $command = new CreateBookCommand(
            title: 'Test',
            year: 2024,
            description: 'Desc',
            isbn: '9780132350884',
            authorIds: []
        );

        $this->assertNull($command->cover);
    }

    public function testUpdateBookCommandStoresAllProperties(): void
    {
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Updated Title',
            year: 2024,
            description: 'New description',
            isbn: '9780132350884',
            authorIds: [1],
            cover: '/uploads/new.jpg'
        );

        $this->assertSame(42, $command->id);
        $this->assertSame('Updated Title', $command->title);
        $this->assertSame(2024, $command->year);
        $this->assertSame('New description', $command->description);
        $this->assertSame('9780132350884', $command->isbn);
        $this->assertSame([1], $command->authorIds);
        $this->assertSame('/uploads/new.jpg', $command->cover);
    }

    public function testDeleteBookCommandStoresId(): void
    {
        $command = new DeleteBookCommand(id: 99);

        $this->assertSame(99, $command->id);
    }
}
