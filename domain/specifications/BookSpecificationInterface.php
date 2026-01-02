<?php

declare(strict_types=1);

namespace app\domain\specifications;

interface BookSpecificationInterface
{
    /**
     * @return array{type: string, value: mixed}
     */
    public function toSearchCriteria(): array;
}
