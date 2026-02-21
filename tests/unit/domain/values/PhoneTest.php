<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\domain\values\Phone;
use Codeception\Test\Unit;

final class PhoneTest extends Unit
{
    public function testCreatesWithValidE164Phone(): void
    {
        $phone = new Phone('+79991234567');

        $this->assertSame('+79991234567', $phone->value);
        $this->assertSame('+79991234567', (string)$phone);
    }

    public function testTrimsWhitespace(): void
    {
        $phone = new Phone('  +79991234567  ');

        $this->assertSame('+79991234567', $phone->value);
    }

    public function testThrowsExceptionForEmptyString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneEmpty->value);

        $phone = new Phone('');
        $this->fail('Expected ValidationException was not thrown, got: ' . $phone->value);
    }

    public function testThrowsExceptionForWhitespaceOnlyString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneEmpty->value);

        $phone = new Phone('   ');
        $this->fail('Expected ValidationException was not thrown, got: ' . $phone->value);
    }

    public function testThrowsExceptionForNonE164Format(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneInvalidFormat->value);

        $phone = new Phone('+7 999 123-45-67');
        $this->fail('Expected ValidationException was not thrown, got: ' . $phone->value);
    }

    public function testThrowsExceptionForUnparseablePhone(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneInvalidFormat->value);

        $phone = new Phone('not-a-phone');
        $this->fail('Expected ValidationException was not thrown, got: ' . $phone->value);
    }

    public function testThrowsExceptionForTooShortPhone(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneInvalidFormat->value);

        $phone = new Phone('+1555');
        $this->fail('Expected ValidationException was not thrown, got: ' . $phone->value);
    }

    public function testThrowsExceptionForPhoneWithoutPlus(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneInvalidFormat->value);

        $phone = new Phone('79991234567');
        $this->fail('Expected ValidationException was not thrown, got: ' . $phone->value);
    }

    public function testEqualsReturnsTrueForSamePhone(): void
    {
        $phone1 = new Phone('+79991234567');
        $phone2 = new Phone('+79991234567');

        $this->assertTrue($phone1->equals($phone2));
    }

    public function testEqualsReturnsFalseForDifferentPhones(): void
    {
        $phone1 = new Phone('+79991234567');
        $phone2 = new Phone('+79991234568');

        $this->assertFalse($phone1->equals($phone2));
    }
}
