<?php

declare(strict_types=1);

namespace app\application\books\commands;

use app\application\ports\CommandInterface;
use app\domain\values\StoredFileReference;

final readonly class UpdateBookCommand implements CommandInterface
{
    /**
     * @param array<int> $authorIds
     */
    public function __construct(
        public int $id,
        public string $title,
        public int $year,
        public ?string $description,
        public string $isbn,
        public array $authorIds,
        public int $version,
        public string|StoredFileReference|null $cover = null,
    ) {
    }
}
