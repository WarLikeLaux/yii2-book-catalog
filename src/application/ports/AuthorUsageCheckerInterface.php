<?php

declare(strict_types=1);

namespace app\application\ports;

interface AuthorUsageCheckerInterface
{
    public function isLinkedToPublishedBooks(int $authorId): bool;

    public function hasSubscriptions(int $authorId): bool;
}
