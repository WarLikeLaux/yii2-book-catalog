<?php

declare(strict_types=1);

namespace tests\unit\presentation\services;

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

    public function testResolveCoverUrlReturnsExistingCoverUrl(): void
    {
        $resolver = new FileUrlResolver('/uploads', 'https://placehold.jp/24/333333/ffffff/400x600.png?text=Book+{seed}');
        $this->assertSame('/uploads/cover.jpg', $resolver->resolveCoverUrl('cover.jpg', 123));
    }

    public function testResolveCoverUrlReturnsPlaceholderWhenCoverIsNull(): void
    {
        $resolver = new FileUrlResolver('/uploads', 'https://placehold.jp/24/333333/ffffff/400x600.png?text=Book+{seed}');
        $this->assertSame('https://placehold.jp/24/333333/ffffff/400x600.png?text=Book+123', $resolver->resolveCoverUrl(null, 123));
    }

    public function testResolveCoverUrlReturnsEmptyWhenNoPlaceholderConfigured(): void
    {
        $resolver = new FileUrlResolver('/uploads');
        $this->assertSame('', $resolver->resolveCoverUrl(null, 123));
    }

    public function testResolveCoverUrlReturnsPlaceholderWhenCoverIsEmpty(): void
    {
        $resolver = new FileUrlResolver('/uploads', 'https://placehold.jp/24/333333/ffffff/400x600.png?text=Book+{seed}');
        $this->assertSame('https://placehold.jp/24/333333/ffffff/400x600.png?text=Book+456', $resolver->resolveCoverUrl('', 456));
    }
}
