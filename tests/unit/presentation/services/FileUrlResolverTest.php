<?php

declare(strict_types=1);

namespace tests\unit\presentation\services;

use app\domain\values\StoredFileReference;
use app\presentation\services\FileUrlResolver;
use Codeception\Test\Unit;

final class FileUrlResolverTest extends Unit
{
    private FileUrlResolver $resolver;

    protected function _before(): void
    {
        $this->resolver = new FileUrlResolver('/uploads');
    }

    public function testResolveReturnsNullForNull(): void
    {
        $this->assertNull($this->resolver->resolve(null));
    }

    public function testResolveReturnsNullForEmptyString(): void
    {
        $this->assertNull($this->resolver->resolve(''));
    }

    public function testResolveReturnsFullUrlForString(): void
    {
        $this->assertSame('/uploads/image.jpg', $this->resolver->resolve('image.jpg'));
    }

    public function testResolveReturnsFullUrlForStoredFileReference(): void
    {
        $ref = new StoredFileReference('covers/book.png');
        $this->assertSame('/uploads/covers/book.png', $this->resolver->resolve($ref));
    }
}
