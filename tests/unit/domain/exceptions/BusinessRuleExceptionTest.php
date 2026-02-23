<?php

declare(strict_types=1);

namespace tests\unit\domain\exceptions;

use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;
use Codeception\Test\Unit;

final class BusinessRuleExceptionTest extends Unit
{
    public function testExceptionHasDefaultHttpCode(): void
    {
        $exception = new BusinessRuleException(DomainErrorCode::BookInvalidStatusTransition);

        $this->assertSame(DomainErrorCode::BookInvalidStatusTransition->value, $exception->getMessage());
        $this->assertSame(422, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionAcceptsCustomValues(): void
    {
        $previous = new \Exception('previous');
        $exception = new BusinessRuleException(DomainErrorCode::BookInvalidStatusTransition, 400, $previous);

        $this->assertSame(400, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
