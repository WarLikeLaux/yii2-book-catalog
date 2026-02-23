<?php

declare(strict_types=1);

namespace app\domain\specifications;

interface BookSpecificationInterface
{
    public function accept(BookSpecificationVisitorInterface $visitor): void;
}
