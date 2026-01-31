<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\handlers;

use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\subscriptions\dto\SubscriptionViewModel;
use app\presentation\subscriptions\forms\SubscriptionForm;
use yii\web\NotFoundHttpException;

final readonly class SubscriptionViewDataFactory
{
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
    ) {
    }

    public function getSubscriptionViewModel(int $authorId, SubscriptionForm|null $form = null): SubscriptionViewModel
    {
        $author = $this->queryService->findById($authorId) ?? throw new NotFoundHttpException();
        $form ??= new SubscriptionForm();
        $form->authorId = $author->id;

        return new SubscriptionViewModel(
            $form,
            $author,
        );
    }
}
