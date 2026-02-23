<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\books\queries\BookReadDto;

interface BookFinderInterface
{
    public function findById(int $id): ?BookReadDto;

    public function findByIdWithAuthors(int $id): ?BookReadDto;
}
