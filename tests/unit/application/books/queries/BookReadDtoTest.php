<?php

declare(strict_types=1);

namespace tests\unit\application\books\queries;

use app\application\books\queries\BookReadDto;
use app\domain\values\BookStatus;
use Codeception\Test\Unit;

final class BookReadDtoTest extends Unit
{
    public function testGetFullTitleWithYear(): void
    {
        $dto = new BookReadDto(
            id: 1,
            title: 'Clean Code',
            year: 2008,
            description: null,
            isbn: '9780132350884',
            authorIds: [1],
            authorNames: [1 => 'Robert C. Martin'],
        );

        $this->assertSame('Clean Code (2008)', $dto->getFullTitle());
    }

    public function testGetFullTitleWithoutYear(): void
    {
        $dto = new BookReadDto(
            id: 1,
            title: 'Unknown Year Book',
            year: null,
            description: null,
            isbn: '9780000000000',
            authorIds: [],
            authorNames: [],
        );

        $this->assertSame('Unknown Year Book', $dto->getFullTitle());
    }

    public function testDefaultsAreApplied(): void
    {
        $dto = new BookReadDto(
            id: 10,
            title: 'Defaults',
            year: null,
            description: null,
            isbn: '9780000000000',
            authorIds: [],
        );

        $this->assertFalse($dto->getIsPublished());
        $this->assertSame(BookStatus::Draft->value, $dto->status);
        $this->assertSame(1, $dto->version);
    }
}
