<?php

declare(strict_types=1);

namespace app\application\ports;

interface PhoneNormalizerInterface
{
    public function normalize(string $phone): string;
}
