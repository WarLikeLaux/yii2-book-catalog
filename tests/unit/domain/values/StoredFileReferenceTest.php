<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\exceptions\ValidationException;
use app\domain\values\StoredFileReference;
use Codeception\Test\Unit;

final class StoredFileReferenceTest extends Unit
{
    public function testGetPathReturnsPath(): void
    {
        $ref = new StoredFileReference('uploads/image.jpg');
        $this->assertSame('uploads/image.jpg', $ref->getPath());
    }

    public function testToStringReturnsPath(): void
    {
        $ref = new StoredFileReference('uploads/file.pdf');
        $this->assertSame('uploads/file.pdf', (string)$ref);
    }

    public function testEmptyPathThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('file.error.path_empty');
        new StoredFileReference('');
    }

    public function testWhitespaceOnlyPathThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('file.error.path_empty');
        new StoredFileReference('   ');
    }
}
