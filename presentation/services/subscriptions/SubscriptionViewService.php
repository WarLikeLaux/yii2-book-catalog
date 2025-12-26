<?php

declare(strict_types=1);

namespace app\presentation\services\subscriptions;

use app\application\authors\queries\AuthorQueryService;
use app\application\authors\queries\AuthorReadDto;

final class SubscriptionViewService
{
    public function __construct(
        private readonly AuthorQueryService $authorQueryService
    ) {
    }

    public function getAuthor(int $authorId): AuthorReadDto
    {
        return $this->authorQueryService->getById($authorId);
    }
}
