<?php

declare(strict_types=1);

namespace app\application\books\commands;

use app\application\ports\CommandInterface;
use app\domain\values\AuthorIdCollection;
use AutoMapper\Attribute\MapFrom;

final readonly class UpdateBookCommand implements CommandInterface
{
    public function __construct(
        public int $id,
        public string $title,
        public int $year,
        public ?string $description,
        public string $isbn,
        #[MapFrom(transformer: [AuthorIdCollection::class, 'fromMixed'])]
        public AuthorIdCollection $authorIds,
        public int $version,
        public string|null $storedCover = null,
    ) {
    }
}
