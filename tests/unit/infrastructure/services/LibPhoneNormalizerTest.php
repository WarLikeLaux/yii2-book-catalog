<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\infrastructure\services\LibPhoneNormalizer;
use Codeception\Test\Unit;
use libphonenumber\PhoneNumberUtil;

final class LibPhoneNormalizerTest extends Unit
{
    private LibPhoneNormalizer $normalizer;

    protected function _before(): void
    {
        $this->normalizer = new LibPhoneNormalizer(PhoneNumberUtil::getInstance());
    }

    public function testNormalizesValidE164(): void
    {
        $this->assertSame('+79991234567', $this->normalizer->normalize('+79991234567'));
    }

    public function testNormalizesPhoneWithSpaces(): void
    {
        $this->assertSame('+79991234567', $this->normalizer->normalize('+7 999 123-45-67'));
    }

    public function testTrimsWhitespace(): void
    {
        $this->assertSame('+79991234567', $this->normalizer->normalize('  +79991234567  '));
    }

    public function testThrowsExceptionForEmptyString(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneEmpty->value);

        $this->normalizer->normalize('');
    }

    public function testThrowsExceptionForWhitespaceOnly(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneEmpty->value);

        $this->normalizer->normalize('   ');
    }

    public function testThrowsExceptionForUnparseablePhone(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneInvalidFormat->value);

        $this->normalizer->normalize('not-a-phone');
    }

    public function testThrowsExceptionForInvalidPhoneNumber(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::PhoneInvalidFormat->value);

        $this->normalizer->normalize('+1555');
    }
}
