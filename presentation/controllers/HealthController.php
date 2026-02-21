<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\ports\HealthCheckRunnerInterface;
use Yii;
use yii\base\Module;
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
        private readonly HealthCheckRunnerInterface $runner,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): Response
    {
        /** @var Response $response */
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;

        $report = $this->runner->run();

        $response->statusCode = $report->healthy ? 200 : 503;
        $checks = [];

        foreach ($report->checks as $check) {
            $checks[$check->name] = array_merge(
                ['status' => $check->healthy ? 'up' : 'down', 'latency_ms' => $check->latencyMs],
                $check->details,
            );
        }

        $response->data = [
            'status' => $report->healthy ? 'healthy' : 'unhealthy',
            'timestamp' => $report->timestamp,
            'version' => $report->version,
            'checks' => $checks,
        ];

        return $response;
    }
}
