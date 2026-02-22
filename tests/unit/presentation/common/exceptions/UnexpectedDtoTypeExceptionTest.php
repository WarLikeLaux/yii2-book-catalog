<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\exceptions;

use app\application\books\queries\BookReadDto;
use app\presentation\common\exceptions\UnexpectedDtoTypeException;
use Codeception\Test\Unit;

final class UnexpectedDtoTypeExceptionTest extends Unit
{
    public function testMessageContainsExpectedAndActualType(): void
    {
        $exception = new UnexpectedDtoTypeException(BookReadDto::class, 'invalid');

        $this->assertStringContainsString(BookReadDto::class, $exception->getMessage());
        $this->assertStringContainsString('string', $exception->getMessage());
    }

    public function testMessageWithNullActual(): void
    {
        $exception = new UnexpectedDtoTypeException(BookReadDto::class, null);

        $this->assertStringContainsString(BookReadDto::class, $exception->getMessage());
        $this->assertStringContainsString('null', $exception->getMessage());
    }
}
