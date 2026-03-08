<?php

declare(strict_types=1);

namespace tests\unit\domain\exceptions;

use app\domain\exceptions\ErrorMapping;
use app\domain\exceptions\ErrorType;
use PHPUnit\Framework\TestCase;

final class ErrorMappingTest extends TestCase
{
    public function testConstructWithTypeOnly(): void
    {
        $mapping = new ErrorMapping(ErrorType::NotFound);

        $this->assertSame(ErrorType::NotFound, $mapping->type);
        $this->assertNull($mapping->field);
    }

    public function testConstructWithTypeAndField(): void
    {
        $mapping = new ErrorMapping(ErrorType::AlreadyExists, 'isbn');

        $this->assertSame(ErrorType::AlreadyExists, $mapping->type);
        $this->assertSame('isbn', $mapping->field);
    }

    public function testAllErrorTypesCovered(): void
    {
        $expected = ['NotFound', 'AlreadyExists', 'OperationFailed', 'BusinessRule'];

        $actual = array_map(
            static fn(ErrorType $type) => $type->name,
            ErrorType::cases(),
        );

        $this->assertSame($expected, $actual);
    }
}
