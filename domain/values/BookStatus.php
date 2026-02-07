<?php

declare(strict_types=1);

namespace app\domain\values;

enum BookStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    private const array TRANSITIONS = [
        'draft' => ['published'],
        'published' => ['draft', 'archived'],
        'archived' => ['draft'],
    ];

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return false;
        }

        return in_array($target->value, self::TRANSITIONS[$this->value], true);
    }
}
