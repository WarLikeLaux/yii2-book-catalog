<?php

declare(strict_types=1);

namespace app\application\ports;

interface BookQueryServiceInterface extends BookFinderInterface, BookSearcherInterface
{
    /**
     * @return string[]
     */
    public function getReferencedCoverKeys(): array;
}
