<?php

declare(strict_types=1);

namespace tests\unit;

use app\application\books\queries\BookReadDto;
use app\presentation\forms\BookForm;
use app\presentation\mappers\BookFormMapper;
use Codeception\Test\Unit;

final class BookFormMapperTest extends Unit
{
    public function testToForm(): void
    {
        $mapper = new BookFormMapper();

        $dto = new BookReadDto(
            id: 1,
            title: 'Test Book',
            year: 2023,
            description: 'Desc',
            isbn: '978-3-16-148410-0',
            authorIds: [1, 2],
            authorNames: [],
            coverUrl: null
        );

        $form = $mapper->toForm($dto);

        $this->assertSame($dto->title, $form->title);
        $this->assertSame($dto->year, $form->year);
        $this->assertSame($dto->description, $form->description);
        $this->assertSame($dto->isbn, $form->isbn);
        $this->assertSame($dto->authorIds, $form->authorIds);
    }

    public function testToCreateCommand(): void
    {
        $mapper = new BookFormMapper();

        $form = new BookForm();
        $form->title = 'New Book';
        $form->year = 2024;
        $form->description = 'Desc';
        $form->isbn = '978-3-16-148410-0';
        $form->authorIds = [1];

        $coverPath = null;
        $command = $mapper->toCreateCommand($form, $coverPath);

        $this->assertSame('New Book', $command->title);
        $this->assertSame(2024, $command->year);
        $this->assertSame('Desc', $command->description);
        $this->assertSame('978-3-16-148410-0', $command->isbn);
        $this->assertSame([1], $command->authorIds);
        $this->assertNull($command->cover);
        $coverPath = '/uploads/cover.jpg';
        $command = $mapper->toCreateCommand($form, $coverPath);
        $this->assertSame($coverPath, $command->cover);
    }

    public function testToUpdateCommand(): void
    {
        $mapper = new BookFormMapper();

        $form = new BookForm();
        $form->title = 'Updated Book';
        $form->year = 2025;
        $form->description = 'Desc 2';
        $form->isbn = '978-3-16-148410-1';
        $form->authorIds = [2];

        $coverPath = 'existing_cover.jpg';
        $command = $mapper->toUpdateCommand(10, $form, $coverPath);

        $this->assertSame(10, $command->id);
        $this->assertSame('Updated Book', $command->title);
        $this->assertSame('existing_cover.jpg', $command->cover);
    }
}
