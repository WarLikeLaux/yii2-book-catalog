<?php

declare(strict_types=1);

namespace app\application\books\factories;

use app\domain\values\BookYear;
use Psr\Clock\ClockInterface;

final readonly class BookYearFactory
{
    public function __construct(
        private ClockInterface $clock
    ) {
    }

    public function create(int $year): BookYear
    {
        return new BookYear($year, $this->clock->now());
    }
}
