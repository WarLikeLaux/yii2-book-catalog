<?php

declare(strict_types=1);

namespace tests\unit\application\common\exceptions;

use app\application\common\exceptions\EntityNotFoundException;
use Codeception\Test\Unit;

final class EntityNotFoundExceptionTest extends Unit
{
    public function testDefaultValues(): void
    {
        $exception = new EntityNotFoundException();

        $this->assertSame('error.entity_not_found', $exception->getMessage());
        $this->assertSame('error.entity_not_found', $exception->errorCode);
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getField());
        $this->assertNull($exception->getPrevious());
    }

    public function testCustomValues(): void
    {
        $previous = new \RuntimeException('db error');
        $exception = new EntityNotFoundException('error.book_not_found', 'book_id', 404, $previous);

        $this->assertSame('error.book_not_found', $exception->getMessage());
        $this->assertSame('error.book_not_found', $exception->errorCode);
        $this->assertSame(404, $exception->getCode());
        $this->assertSame('book_id', $exception->getField());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
