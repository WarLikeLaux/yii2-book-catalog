<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

interface DomainErrorMappingProviderInterface
{
    public function registerMappings(DomainErrorMappingRegistry $registry): void;
}
