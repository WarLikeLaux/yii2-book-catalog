<?php

declare(strict_types=1);

namespace tests\unit\domain\exceptions;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use Codeception\Test\Unit;

final class ValidationExceptionTest extends Unit
{
    public function testExceptionHasDefaultHttpCode(): void
    {
        $exception = new ValidationException(DomainErrorCode::BookTitleEmpty);

        $this->assertSame(DomainErrorCode::BookTitleEmpty->value, $exception->getMessage());
        $this->assertSame(422, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionAcceptsCustomValues(): void
    {
        $previous = new \Exception('previous');
        $exception = new ValidationException(DomainErrorCode::BookTitleEmpty, 400, $previous);

        $this->assertSame(400, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
