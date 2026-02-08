<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

interface FieldAwareExceptionInterface
{
    public function getField(): ?string;
}
