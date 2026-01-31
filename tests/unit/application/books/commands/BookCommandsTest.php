<?php

declare(strict_types=1);

namespace tests\unit\application\books\commands;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\DeleteBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\domain\values\AuthorIdCollection;
use app\domain\values\StoredFileReference;
use Codeception\Test\Unit;

final class BookCommandsTest extends Unit
{
    public function testCreateBookCommandStoresAllProperties(): void
    {
        $cover = new StoredFileReference('/uploads/cover.jpg');
        $command = new CreateBookCommand(
            title: 'Clean Code',
            year: 2008,
            description: 'A book about clean code',
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1, 2, 3]),
            storedCover: $cover,
        );

        $this->assertSame('Clean Code', $command->title);
        $this->assertSame(2008, $command->year);
        $this->assertSame('A book about clean code', $command->description);
        $this->assertSame('9780132350884', $command->isbn);
        $this->assertSame([1, 2, 3], $command->authorIds->toArray());
        $this->assertSame($cover, $command->storedCover);
    }

    public function testCreateBookCommandWithDefaultCover(): void
    {
        $command = new CreateBookCommand(
            title: 'Test',
            year: 2024,
            description: 'Desc',
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([]),
        );

        $this->assertNull($command->storedCover);
    }

    public function testUpdateBookCommandStoresAllProperties(): void
    {
        $cover = new StoredFileReference('/uploads/new.jpg');
        $command = new UpdateBookCommand(
            id: 42,
            title: 'Updated Title',
            year: 2024,
            description: 'New description',
            isbn: '9780132350884',
            authorIds: AuthorIdCollection::fromArray([1]),
            version: 5,
            storedCover: $cover,
        );

        $this->assertSame(42, $command->id);
        $this->assertSame('Updated Title', $command->title);
        $this->assertSame(2024, $command->year);
        $this->assertSame('New description', $command->description);
        $this->assertSame('9780132350884', $command->isbn);
        $this->assertSame([1], $command->authorIds->toArray());
        $this->assertSame(5, $command->version);
        $this->assertSame($cover, $command->storedCover);
    }

    public function testDeleteBookCommandStoresId(): void
    {
        $command = new DeleteBookCommand(id: 99);

        $this->assertSame(99, $command->id);
    }
}
