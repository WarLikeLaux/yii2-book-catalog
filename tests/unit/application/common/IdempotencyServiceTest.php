<?php

declare(strict_types=1);

namespace app\tests\unit\application\common;

use app\application\common\IdempotencyService;
use app\application\ports\IdempotencyInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class IdempotencyServiceTest extends Unit
{
    private IdempotencyInterface&MockObject $repository;
    private IdempotencyService $service;

    protected function _before(): void
    {
        $this->repository = $this->createMock(IdempotencyInterface::class);
        $this->service = new IdempotencyService($this->repository);
    }

    public function testGetCacheReturnsDtoWhenKeyExists(): void
    {
        $key = 'test-key';
        $savedData = [
            'status_code' => 201,
            'body' => (string)json_encode(['id' => 123, 'title' => 'Test Book']),
        ];

        $this->repository->expects($this->once())
            ->method('getResponse')
            ->with($key)
            ->willReturn($savedData);

        $result = $this->service->getResponse($key);

        $this->assertNotNull($result);
        $this->assertSame(201, $result->statusCode);
        $this->assertSame(['id' => 123, 'title' => 'Test Book'], $result->data);
        $this->assertNull($result->redirectUrl);
    }

    public function testGetCacheReturnsDtoWithRedirect(): void
    {
        $key = 'test-key';
        $savedData = [
            'status_code' => 302,
            'body' => (string)json_encode(['redirect_url' => '/view/123']),
        ];

        $this->repository->expects($this->once())
            ->method('getResponse')
            ->with($key)
            ->willReturn($savedData);

        $result = $this->service->getResponse($key);

        $this->assertNotNull($result);
        $this->assertSame(302, $result->statusCode);
        $this->assertSame('/view/123', $result->redirectUrl);
    }

    public function testSaveResponseEncodesDataCorrectly(): void
    {
        $key = 'test-key';
        $result = ['id' => 456];
        $ttl = 3600;

        $this->repository->expects($this->once())
            ->method('saveResponse')
            ->with($key, 200, (string)json_encode($result), $ttl);

        $this->service->saveResponse($key, 200, $result, null, $ttl);
    }

    public function testSaveResponseEncodesRedirect(): void
    {
        $key = 'test-key';
        $redirectUrl = '/success';
        $ttl = 3600;

        $this->repository->expects($this->once())
            ->method('saveResponse')
            ->with($key, 302, (string)json_encode(['redirect_url' => $redirectUrl]), $ttl);

        $this->service->saveResponse($key, 302, [], $redirectUrl, $ttl);
    }
}
