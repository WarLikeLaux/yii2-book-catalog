<?php

declare(strict_types=1);

namespace tests\unit\domain\exceptions;

use app\domain\exceptions\AlreadyExistsException;
use Codeception\Test\Unit;

final class AlreadyExistsExceptionTest extends Unit
{
    public function testExceptionHasDefaultValues(): void
    {
        $exception = new AlreadyExistsException();

        $this->assertSame('error.entity_already_exists', $exception->getMessage());
        $this->assertSame(409, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionAcceptsCustomValues(): void
    {
        $previous = new \Exception('previous');
        $exception = new AlreadyExistsException('custom.error', 400, $previous);

        $this->assertSame('custom.error', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
