<?php

declare(strict_types=1);

namespace tests\unit\presentation\controllers;

use app\application\common\dto\HealthCheckResult;
use app\application\common\dto\HealthReport;
use app\application\ports\HealthCheckRunnerInterface;
use app\presentation\controllers\HealthController;
use Codeception\Test\Unit;
use Yii;
use yii\base\Module;
use yii\web\Application;
use yii\web\Request;
use yii\web\Response;

final class HealthControllerTest extends Unit
{
    private $originalApp;

    protected function _before(): void
    {
        $this->originalApp = Yii::$app;

        $app = $this->createMock(Application::class);
        $request = new Request();
        $response = new Response();

        $app->method('getRequest')->willReturn($request);
        $app->method('getResponse')->willReturn($response);

        Yii::$app = $app;
    }

    protected function _after(): void
    {
        Yii::$app = $this->originalApp;
    }

    public function testIndexSuccess(): void
    {
        $runner = $this->createMock(HealthCheckRunnerInterface::class);
        $report = new HealthReport(
            healthy: true,
            checks: [
                new HealthCheckResult('test', true, 1.0, ['info' => 'ok']),
            ],
            version: '1.0.0',
            timestamp: '2026-02-08T03:52:00+06:00',
        );
        $runner->method('run')->willReturn($report);

        $controller = new HealthController('health', $this->createMock(Module::class), $runner, [
            'request' => new Request(),
            'response' => new Response(),
        ]);

        $response = $controller->actionIndex();

        $this->assertSame(200, $response->statusCode);
        $this->assertSame(Response::FORMAT_JSON, $response->format);

        /** @var array<string, mixed> $data */
        $data = $response->data;

        $this->assertSame('healthy', $data['status']);
        $this->assertSame('2026-02-08T03:52:00+06:00', $data['timestamp']);
        $this->assertSame('1.0.0', $data['version']);
        $this->assertSame('up', $data['checks']['test']['status']);
        $this->assertSame(1.0, $data['checks']['test']['latency_ms']);
        $this->assertSame('ok', $data['checks']['test']['info']);
    }

    public function testIndexFailure(): void
    {
        $runner = $this->createMock(HealthCheckRunnerInterface::class);
        $report = new HealthReport(
            healthy: false,
            checks: [
                new HealthCheckResult('test', false, 1.0, ['info' => 'error']),
            ],
            version: '1.0.0',
            timestamp: '2026-02-08T03:52:00+06:00',
        );
        $runner->method('run')->willReturn($report);

        $controller = new HealthController('health', $this->createMock(Module::class), $runner, [
            'request' => new Request(),
            'response' => new Response(),
        ]);

        $response = $controller->actionIndex();

        $this->assertSame(503, $response->statusCode);
        $this->assertSame(Response::FORMAT_JSON, $response->format);

        /** @var array<string, mixed> $data */
        $data = $response->data;

        $this->assertSame('unhealthy', $data['status']);
        $this->assertSame('down', $data['checks']['test']['status']);
    }
}
