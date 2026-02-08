<?php

declare(strict_types=1);

namespace tests\unit\application\common\exceptions;

use app\application\common\exceptions\AlreadyExistsException;
use Codeception\Test\Unit;

final class AlreadyExistsExceptionTest extends Unit
{
    public function testDefaultValues(): void
    {
        $exception = new AlreadyExistsException('error.entity_already_exists');

        $this->assertSame('error.entity_already_exists', $exception->getMessage());
        $this->assertSame('error.entity_already_exists', $exception->errorCode);
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getField());
        $this->assertNull($exception->getPrevious());
    }

    public function testCustomValues(): void
    {
        $previous = new \RuntimeException('db error');
        $exception = new AlreadyExistsException('author.error.fio_exists', 'fio', 409, $previous);

        $this->assertSame('author.error.fio_exists', $exception->getMessage());
        $this->assertSame('author.error.fio_exists', $exception->errorCode);
        $this->assertSame(409, $exception->getCode());
        $this->assertSame('fio', $exception->getField());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
