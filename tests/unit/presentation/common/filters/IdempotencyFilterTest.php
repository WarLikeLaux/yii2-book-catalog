<?php

declare(strict_types=1);

namespace app\tests\unit\presentation\filters;

use app\application\common\dto\IdempotencyResponseDto;
use app\application\common\IdempotencyServiceInterface;
use app\presentation\common\filters\IdempotencyFilter;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Yii;
use yii\base\Action;
use yii\web\Controller;
use yii\web\HeaderCollection;
use yii\web\Request;
use yii\web\Response;

final class IdempotencyFilterTest extends Unit
{
    private IdempotencyServiceInterface&MockObject $service;
    private IdempotencyFilter $filter;
    private Action $action;

    protected function _before(): void
    {
        $this->service = $this->createMock(IdempotencyServiceInterface::class);
        $this->filter = new IdempotencyFilter($this->service);
        
        $controller = $this->createMock(Controller::class);
        $this->action = new Action('test', $controller);
    }

    public function testBeforeActionReturnsTrueOnNonPost(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getIsPost')->willReturn(false);
        Yii::$app->set('request', $request);

        $this->assertTrue($this->filter->beforeAction($this->action));
    }

    public function testBeforeActionReturnsTrueOnMissingHeader(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getIsPost')->willReturn(true);
        $request->method('getHeaders')->willReturn(new HeaderCollection());
        Yii::$app->set('request', $request);

        $this->assertTrue($this->filter->beforeAction($this->action));
    }

    public function testBeforeActionRestoresResponseOnHit(): void
    {
        $key = 'test-key';
        $headers = new HeaderCollection();
        $headers->set('Idempotency-Key', $key);

        $request = $this->createMock(Request::class);
        $request->method('getIsPost')->willReturn(true);
        $request->method('getHeaders')->willReturn($headers);
        Yii::$app->set('request', $request);

        $cachedResponse = new IdempotencyResponseDto(
            statusCode: 201,
            data: ['id' => 1],
            redirectUrl: null
        );

        $this->service->expects($this->once())
            ->method('getResponse')
            ->with($key)
            ->willReturn($cachedResponse);

        $response = new Response();
        Yii::$app->set('response', $response);

        $this->assertFalse($this->filter->beforeAction($this->action));
        $this->assertSame(201, $response->statusCode);
        $this->assertSame(['id' => 1], $response->data);
        $this->assertSame('HIT', $response->getHeaders()->get('X-Idempotency-Cache'));
    }

    public function testAfterActionCachesResponseOnMiss(): void
    {
        $key = 'test-key';
        $headers = new HeaderCollection();
        $headers->set('Idempotency-Key', $key);

        $request = $this->createMock(Request::class);
        $request->method('getIsPost')->willReturn(true);
        $request->method('getHeaders')->willReturn($headers);
        Yii::$app->set('request', $request);

        $response = new Response();
        $response->statusCode = 200;
        Yii::$app->set('response', $response);

        $result = ['status' => 'ok'];

        $this->service->expects($this->once())
            ->method('saveResponse')
            ->with($key, 200, $result, null, 86400);

        $this->filter->afterAction($this->action, $result);
        $this->assertSame('MISS', $response->getHeaders()->get('X-Idempotency-Cache'));
    }
}