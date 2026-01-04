<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\viewmodels;

use app\application\books\queries\BookReadDto;
use app\presentation\books\viewmodels\BookViewModel;
use app\presentation\services\FileUrlResolver;
use Codeception\Test\Unit;

final class BookViewModelTest extends Unit
{
    private FileUrlResolver $resolver;

    protected function _before(): void
    {
        $this->resolver = new FileUrlResolver('/uploads');
    }

    public function testGetFullTitleWithYear(): void
    {
        $dto = new BookReadDto(
            id: 1,
            title: 'Clean Code',
            year: 2008,
            description: 'A book',
            isbn: '9780132350884',
            authorIds: [1],
            authorNames: ['Robert Martin'],
            coverUrl: null,
            isPublished: true,
            version: 1
        );

        $viewModel = new BookViewModel($dto, $this->resolver);

        $this->assertSame('Clean Code (2008)', $viewModel->getFullTitle());
    }

    public function testGetFullTitleWithoutYear(): void
    {
        $dto = new BookReadDto(
            id: 1,
            title: 'Clean Code',
            year: null,
            description: 'A book',
            isbn: '9780132350884',
            authorIds: [1],
            authorNames: ['Robert Martin'],
            coverUrl: null,
            isPublished: true,
            version: 1
        );

        $viewModel = new BookViewModel($dto, $this->resolver);

        $this->assertSame('Clean Code', $viewModel->getFullTitle());
    }

    public function testCoverUrlResolved(): void
    {
        $dto = new BookReadDto(
            id: 1,
            title: 'Test',
            year: 2020,
            description: null,
            isbn: '9780132350884',
            authorIds: [],
            authorNames: [],
            coverUrl: 'cover.jpg',
            isPublished: false,
            version: 1
        );

        $viewModel = new BookViewModel($dto, $this->resolver);

        $this->assertSame('/uploads/cover.jpg', $viewModel->coverUrl);
    }

    public function testJsonSerialize(): void
    {
        $dto = new BookReadDto(
            id: 5,
            title: 'Test Book',
            year: 2021,
            description: 'Desc',
            isbn: '1234567890123',
            authorIds: [1, 2],
            authorNames: ['A', 'B'],
            coverUrl: 'c.jpg',
            isPublished: true,
            version: 3
        );

        $viewModel = new BookViewModel($dto, $this->resolver);
        $json = $viewModel->jsonSerialize();

        $this->assertSame(5, $json['id']);
        $this->assertSame('Test Book', $json['title']);
        $this->assertSame(2021, $json['year']);
        $this->assertSame('/uploads/c.jpg', $json['coverUrl']);
    }
}
