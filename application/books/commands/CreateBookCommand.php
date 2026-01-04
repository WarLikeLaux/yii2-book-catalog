<?php

declare(strict_types=1);

namespace app\application\books\commands;

use app\domain\values\StoredFileReference;

final readonly class CreateBookCommand
{
    /**
     * @param array<int> $authorIds
     */
    public function __construct(
        public string $title,
        public int $year,
        public ?string $description,
        public string $isbn,
        public array $authorIds,
        public string|StoredFileReference|null $cover = null,
    ) {
    }
}
