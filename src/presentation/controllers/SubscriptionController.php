<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\ports\RequestIdProviderInterface;
use app\presentation\common\dto\ApiResponse;
use app\presentation\common\enums\ActionName;
use app\presentation\common\filters\IdempotencyFilter;
use app\presentation\common\traits\HtmxDetectionTrait;
use app\presentation\common\ViewModelRenderer;
use app\presentation\subscriptions\handlers\SubscriptionCommandHandler;
use app\presentation\subscriptions\handlers\SubscriptionViewFactory;
use Override;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class SubscriptionController extends BaseController
{
    use HtmxDetectionTrait;

    public function __construct(
        $id,
        $module,
        private readonly SubscriptionCommandHandler $commandHandler,
        private readonly SubscriptionViewFactory $viewFactory,
        ViewModelRenderer $renderer,
        RequestIdProviderInterface $requestIdProvider,
        $config = [],
    ) {
        parent::__construct($id, $module, $renderer, $requestIdProvider, $config);
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            'idempotency' => [
                'class' => IdempotencyFilter::class,
                'only' => [ActionName::SUBSCRIBE->value],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    ActionName::SUBSCRIBE->value => ['post'],
                ],
            ],
        ];
    }

    public function actionSubscribe(): Response
    {
        $form = $this->viewFactory->createForm();

        if ($this->request->isPost && $form->loadFromRequest($this->request) && $form->validate()) {
            return $this->asJson($this->commandHandler->subscribe($form));
        }

        return $this->asJson(ApiResponse::failure(
            Yii::t('app', 'error.validation'),
            $form->errors,
        ));
    }

    public function actionForm(int $authorId): string
    {
        $viewModel = $this->viewFactory->getSubscriptionViewModel($authorId);

        if ($this->isHtmxRequest()) {
            return $this->renderer->renderPartial('_form', [
                'viewModel' => $viewModel,
            ]);
        }

        return $this->renderer->render('form', $viewModel);
    }
}
