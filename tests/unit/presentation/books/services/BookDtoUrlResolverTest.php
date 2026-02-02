<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\services;

use app\application\books\queries\BookReadDto;
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\services\FileUrlResolver;
use Codeception\Test\Unit;

final class BookDtoUrlResolverTest extends Unit
{
    private FileUrlResolver $fileUrlResolver;
    private BookDtoUrlResolver $resolver;

    protected function _before(): void
    {
        $this->fileUrlResolver = new FileUrlResolver('/uploads');
        $this->resolver = new BookDtoUrlResolver($this->fileUrlResolver);
    }

    public function testResolveUrlReturnsDtoWithResolvedCover(): void
    {
        $dto = new BookReadDto(1, 'Title', 2020, null, 'ISBN', [], [], 'cover.jpg', false, 1);

        $result = $this->resolver->resolveUrl($dto);

        $this->assertSame('/uploads/cover.jpg', $result->coverUrl);
    }

    public function testResolveUrlReturnsPlaceholderWhenEmpty(): void
    {
        $dto = new BookReadDto(1, 'Title', 2020, null, 'ISBN', [], [], null, false, 1);

        $result = $this->resolver->resolveUrl($dto);

        $this->assertEquals('', $result->coverUrl);
    }
}
