<?php

declare(strict_types=1);

namespace app\application\ports;

interface CoverKeysScannerInterface
{
    /**
     * @return string[]
     */
    public function getReferencedCoverKeys(): array;
}
