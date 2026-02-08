<?php

declare(strict_types=1);

namespace tests\unit\domain\exceptions;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use Codeception\Test\Unit;

final class EntityNotFoundExceptionTest extends Unit
{
    public function testExceptionHasDefaultValues(): void
    {
        $exception = new EntityNotFoundException(DomainErrorCode::AuthorNotFound);

        $this->assertSame(DomainErrorCode::AuthorNotFound->value, $exception->getMessage());
        $this->assertSame(404, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionAcceptsCustomValues(): void
    {
        $previous = new \Exception('previous');
        $exception = new EntityNotFoundException(DomainErrorCode::BookNotFound, 400, $previous);

        $this->assertSame(DomainErrorCode::BookNotFound->value, $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
