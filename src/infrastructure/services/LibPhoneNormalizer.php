<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use app\application\ports\PhoneNormalizerInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final readonly class LibPhoneNormalizer implements PhoneNormalizerInterface
{
    public function __construct(
        private PhoneNumberUtil $phoneUtil,
    ) {
    }

    public function normalize(string $phone): string
    {
        $trimmed = trim($phone);

        if ($trimmed === '') {
            throw new ValidationException(DomainErrorCode::PhoneEmpty);
        }

        try {
            $parsed = $this->phoneUtil->parse($trimmed, PhoneNumberUtil::UNKNOWN_REGION);
        } catch (NumberParseException) {
            throw new ValidationException(DomainErrorCode::PhoneInvalidFormat);
        }

        if (!$this->phoneUtil->isValidNumber($parsed)) {
            throw new ValidationException(DomainErrorCode::PhoneInvalidFormat);
        }

        return $this->phoneUtil->format($parsed, PhoneNumberFormat::E164);
    }
}
