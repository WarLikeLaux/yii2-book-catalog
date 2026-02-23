<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\handlers;

use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\subscriptions\dto\SubscriptionViewModel;
use app\presentation\subscriptions\forms\SubscriptionForm;
use yii\web\NotFoundHttpException;

final readonly class SubscriptionViewFactory
{
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
    ) {
    }

    public function getSubscriptionViewModel(int $authorId, SubscriptionForm|null $form = null): SubscriptionViewModel
    {
        $author = $this->queryService->findById($authorId) ?? throw new NotFoundHttpException();
        $form ??= $this->createForm($author->id);

        return new SubscriptionViewModel(
            $form,
            $author,
        );
    }

    public function createForm(int|null $authorId = null): SubscriptionForm
    {
        $form = new SubscriptionForm();

        if ($authorId !== null) {
            $form->authorId = $authorId;
        }

        return $form;
    }
}
