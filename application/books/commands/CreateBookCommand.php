<?php

declare(strict_types=1);

namespace app\application\books\commands;

use app\application\ports\CommandInterface;
use app\domain\values\AuthorIdCollection;
use app\domain\values\StoredFileReference;
use AutoMapper\Attribute\MapFrom;

final readonly class CreateBookCommand implements CommandInterface
{
    public function __construct(
        public string $title,
        public int $year,
        public ?string $description,
        public string $isbn,
        #[MapFrom(transformer: [AuthorIdCollection::class, 'fromMixed'])]
        public AuthorIdCollection $authorIds,
        public StoredFileReference|null $storedCover = null,
    ) {
    }
}
