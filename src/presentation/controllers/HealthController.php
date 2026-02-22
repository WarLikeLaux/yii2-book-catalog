<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\system\commands\CheckHealthCommand;
use app\application\system\usecases\CheckHealthUseCase;
use app\presentation\services\HealthResponseFormatter;
use Override;
use yii\base\Module;
use yii\filters\AccessControl;
use yii\rest\Controller;
use yii\web\Response;

final class HealthController extends Controller
{
    /**
     * @param string $id
     * @param Module $module
     * @param array<string, mixed> $config
     */
    public function __construct(
        $id,
        $module,
        private readonly CheckHealthUseCase $useCase,
        private readonly HealthResponseFormatter $formatter,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    #[Override]
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                ],
            ],
        ];

        return $behaviors;
    }

    public function actionIndex(): Response
    {
        /** @var Response $response */
        $response = $this->response;
        $response->format = Response::FORMAT_JSON;

        $report = $this->useCase->execute(new CheckHealthCommand());

        $response->statusCode = $report->healthy ? 200 : 503;
        $response->data = $this->formatter->format($report);

        return $response;
    }
}
