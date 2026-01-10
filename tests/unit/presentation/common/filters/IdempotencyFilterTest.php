<?php

declare(strict_types=1);

namespace app\tests\unit\presentation\common\filters;

use app\application\common\config\IdempotencyConfig;
use app\application\common\dto\IdempotencyRecordDto;
use app\application\common\IdempotencyKeyStatus;
use app\application\common\IdempotencyServiceInterface;
use app\presentation\common\filters\IdempotencyFilter;
use Codeception\Test\Unit;
use Yii;
use yii\base\Action;
use yii\web\Controller;
use yii\web\Request;
use yii\web\Response;

final class IdempotencyFilterTest extends Unit
{
    private string|null $originalMethod = null;

    protected function _before(): void
    {
        $this->originalMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->set('request', new Request());
        Yii::$app->set('response', new Response());
    }

    protected function _after(): void
    {
        if ($this->originalMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
            return;
        }

        $_SERVER['REQUEST_METHOD'] = $this->originalMethod;
    }

    public function testBeforeActionReturnsUnavailableWhenStartFails(): void
    {
        $service = $this->createMock(IdempotencyServiceInterface::class);
        $service->expects($this->once())
            ->method('acquireLock')
            ->with('key-1', 1)
            ->willReturn(true);
        $service->expects($this->once())
            ->method('getRecord')
            ->with('key-1')
            ->willReturn(null);
        $service->expects($this->once())
            ->method('startRequest')
            ->with('key-1', 86400)
            ->willReturn(false);
        $service->expects($this->once())
            ->method('releaseLock')
            ->with('key-1');

        Yii::$app->request->getHeaders()->set('Idempotency-Key', 'key-1');

        $filter = new IdempotencyFilter($service, $this->createConfig());
        $result = $filter->beforeAction($this->createAction());

        $this->assertFalse($result);
        $this->assertSame(503, Yii::$app->response->statusCode);
        $this->assertSame('UNAVAILABLE', Yii::$app->response->getHeaders()->get('X-Idempotency-Status'));
    }

    public function testBeforeActionReturnsInProgressWhenRecordStarted(): void
    {
        $service = $this->createMock(IdempotencyServiceInterface::class);
        $service->expects($this->once())
            ->method('acquireLock')
            ->with('key-2', 1)
            ->willReturn(true);
        $service->expects($this->once())
            ->method('getRecord')
            ->with('key-2')
            ->willReturn(new IdempotencyRecordDto(
                IdempotencyKeyStatus::Started,
                null,
                [],
                null,
            ));
        $service->expects($this->never())
            ->method('startRequest');
        $service->expects($this->once())
            ->method('releaseLock')
            ->with('key-2');

        Yii::$app->request->getHeaders()->set('Idempotency-Key', 'key-2');

        $filter = new IdempotencyFilter($service, $this->createConfig());
        $result = $filter->beforeAction($this->createAction());

        $this->assertFalse($result);
        $this->assertSame(409, Yii::$app->response->statusCode);
        $this->assertSame('IN_PROGRESS', Yii::$app->response->getHeaders()->get('X-Idempotency-Status'));
    }

    public function testBeforeActionReturnsCachedResponseWhenFinished(): void
    {
        $service = $this->createMock(IdempotencyServiceInterface::class);
        $service->expects($this->once())
            ->method('acquireLock')
            ->with('key-3', 1)
            ->willReturn(true);
        $service->expects($this->once())
            ->method('getRecord')
            ->with('key-3')
            ->willReturn(new IdempotencyRecordDto(
                IdempotencyKeyStatus::Finished,
                201,
                ['id' => 10],
                null,
            ));
        $service->expects($this->never())
            ->method('startRequest');
        $service->expects($this->once())
            ->method('releaseLock')
            ->with('key-3');

        Yii::$app->request->getHeaders()->set('Idempotency-Key', 'key-3');

        $filter = new IdempotencyFilter($service, $this->createConfig());
        $result = $filter->beforeAction($this->createAction());

        $this->assertFalse($result);
        $this->assertSame(201, Yii::$app->response->statusCode);
        $this->assertSame(['id' => 10], Yii::$app->response->data);
        $this->assertSame('HIT', Yii::$app->response->getHeaders()->get('X-Idempotency-Cache'));
    }

    private function createAction(): Action
    {
        return new Action('test', new Controller('test', Yii::$app));
    }

    private function createConfig(): IdempotencyConfig
    {
        return new IdempotencyConfig(86400, 1, 1, 'hash-key');
    }
}
