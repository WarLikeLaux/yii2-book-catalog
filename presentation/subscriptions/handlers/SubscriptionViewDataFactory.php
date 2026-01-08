<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use yii\web\NotFoundHttpException;

final readonly class SubscriptionViewDataFactory
{
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
    ) {
    }

    public function getAuthor(int $authorId): AuthorReadDto
    {
        return $this->queryService->findById($authorId)
            ?? throw new NotFoundHttpException();
    }
}
