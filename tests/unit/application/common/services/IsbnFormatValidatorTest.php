<?php

declare(strict_types=1);

namespace tests\unit\application\common\services;

use app\application\common\services\IsbnFormatValidator;
use Codeception\Test\Unit;

final class IsbnFormatValidatorTest extends Unit
{
    private IsbnFormatValidator $validator;

    protected function _before(): void
    {
        $this->validator = new IsbnFormatValidator();
    }

    public function testIsValidReturnsTrueForValidIsbn10(): void
    {
        $this->assertTrue($this->validator->isValid('0-306-40615-2'));
    }

    public function testIsValidReturnsTrueForValidIsbn13(): void
    {
        $this->assertTrue($this->validator->isValid('978-3-16-148410-0'));
    }

    public function testIsValidReturnsFalseForInvalidIsbn(): void
    {
        $this->assertFalse($this->validator->isValid('invalid-isbn'));
    }

    public function testIsValidReturnsFalseForInvalidChecksum(): void
    {
        $this->assertFalse($this->validator->isValid('0-306-40615-1')); // Invalid check digit
    }
}
