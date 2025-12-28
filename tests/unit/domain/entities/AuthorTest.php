<?php

declare(strict_types=1);

namespace tests\unit\domain\entities;

use app\domain\entities\Author;
use Codeception\Test\Unit;

final class AuthorTest extends Unit
{
    public function testCreateAndGetters(): void
    {
        $author = Author::create('Test FIO');
        
        $this->assertNull($author->getId());
        $this->assertSame('Test FIO', $author->getFio());
        
        $author->setId(123);
        $this->assertSame(123, $author->getId());
    }

    public function testUpdate(): void
    {
        $author = Author::create('Old Name');
        $this->assertSame('Old Name', $author->getFio());

        $author->update('New Name');
        $this->assertSame('New Name', $author->getFio());
    }

    public function testConstructor(): void
    {
        $author = new Author(555, 'Direct Create');
        $this->assertSame(555, $author->getId());
        $this->assertSame('Direct Create', $author->getFio());
    }
}
