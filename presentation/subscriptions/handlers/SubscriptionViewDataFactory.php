<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use yii\web\NotFoundHttpException;

final readonly class SubscriptionViewDataFactory
{
    /**
     * Initialize the factory with the author query service.
     *
     * @param AuthorQueryServiceInterface $queryService Service used to retrieve author data (e.g., findById) for view model creation.
     */
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
    ) {
    }

    /**
     * Retrieve the author read DTO for the given author ID.
     *
     * @param int $authorId The identifier of the author to retrieve.
     * @return AuthorReadDto The author's read DTO.
     * @throws NotFoundHttpException If no author exists with the provided ID.
     */
    public function getAuthor(int $authorId): AuthorReadDto
    {
        return $this->queryService->findById($authorId)
            ?? throw new NotFoundHttpException();
    }
}