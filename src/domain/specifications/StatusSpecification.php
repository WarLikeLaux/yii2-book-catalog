<?php

declare(strict_types=1);

namespace app\domain\specifications;

use app\domain\values\BookStatus;

final readonly class StatusSpecification implements BookSpecificationInterface
{
    public function __construct(
        private BookStatus $status,
    ) {
    }

    public function accept(BookSpecificationVisitorInterface $visitor): void
    {
        $visitor->visitStatus($this);
    }

    public function getStatus(): BookStatus
    {
        return $this->status;
    }
}
