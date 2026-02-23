<?php

declare(strict_types=1);

namespace tests\unit\application\common;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\IdempotencyRecordDto;
use app\application\common\IdempotencyKeyStatus;
use app\application\common\IdempotencyService;
use app\application\ports\IdempotencyInterface;
use app\application\ports\MutexInterface;
use app\domain\values\BookStatus;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class IdempotencyServiceTest extends Unit
{
    private IdempotencyInterface&MockObject $repository;
    private MutexInterface&MockObject $mutex;
    private IdempotencyService $service;

    protected function _before(): void
    {
        $this->repository = $this->createMock(IdempotencyInterface::class);
        $this->mutex = $this->createMock(MutexInterface::class);
        $this->service = new IdempotencyService($this->repository, $this->mutex);
    }

    public function testGetRecordReturnsFinishedDtoWhenKeyExists(): void
    {
        $key = 'test-key';
        $dto = new IdempotencyRecordDto(
            IdempotencyKeyStatus::Finished,
            201,
            ['id' => 123, 'title' => 'Test Book'],
            null,
        );

        $this->repository->expects($this->once())
            ->method('getRecord')
            ->with($key)
            ->willReturn($dto);

        $result = $this->service->getRecord($key);

        $this->assertNotNull($result);
        $this->assertSame(IdempotencyKeyStatus::Finished, $result->status);
        $this->assertSame(201, $result->statusCode);
        $this->assertSame(['id' => 123, 'title' => 'Test Book'], $result->data);
        $this->assertNull($result->redirectUrl);
    }

    public function testGetRecordReturnsDtoWithRedirect(): void
    {
        $key = 'test-key';
        $dto = new IdempotencyRecordDto(
            IdempotencyKeyStatus::Finished,
            302,
            ['redirect_url' => '/view/123'],
            '/view/123',
        );

        $this->repository->expects($this->once())
            ->method('getRecord')
            ->with($key)
            ->willReturn($dto);

        $result = $this->service->getRecord($key);

        $this->assertNotNull($result);
        $this->assertSame(IdempotencyKeyStatus::Finished, $result->status);
        $this->assertSame(302, $result->statusCode);
        $this->assertSame('/view/123', $result->redirectUrl);
    }

    public function testGetRecordReturnsStartedDto(): void
    {
        $key = 'test-key';
        $dto = new IdempotencyRecordDto(IdempotencyKeyStatus::Started, null, [], null);

        $this->repository->expects($this->once())
            ->method('getRecord')
            ->with($key)
            ->willReturn($dto);

        $result = $this->service->getRecord($key);

        $this->assertNotNull($result);
        $this->assertSame(IdempotencyKeyStatus::Started, $result->status);
        $this->assertNull($result->statusCode);
        $this->assertSame([], $result->data);
        $this->assertNull($result->redirectUrl);
    }

    public function testGetRecordIgnoresPayloadWhenStatusStarted(): void
    {
        $key = 'test-key';
        $dto = new IdempotencyRecordDto(IdempotencyKeyStatus::Started, null, [], null);

        $this->repository->expects($this->once())
            ->method('getRecord')
            ->with($key)
            ->willReturn($dto);

        $result = $this->service->getRecord($key);

        $this->assertNotNull($result);
        $this->assertSame(IdempotencyKeyStatus::Started, $result->status);
        $this->assertNull($result->statusCode);
        $this->assertSame([], $result->data);
        $this->assertNull($result->redirectUrl);
    }

    public function testGetRecordReturnsNullWhenRepositoryReturnsNull(): void
    {
        $key = 'test-key';

        $this->repository->expects($this->once())
            ->method('getRecord')
            ->with($key)
            ->willReturn(null);

        $result = $this->service->getRecord($key);

        $this->assertNull($result);
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

    public function testStartRequestDelegatesToRepository(): void
    {
        $key = 'test-key';
        $ttl = 3600;

        $this->repository->expects($this->once())
            ->method('saveStarted')
            ->with($key, $ttl)
            ->willReturn(true);

        $this->assertTrue($this->service->startRequest($key, $ttl));
    }

    public function testSaveResponsePreservesArrayPayload(): void
    {
        $key = 'test-key';
        $result = ['id' => 456, 'title' => 'Test Book'];
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

    public function testSaveResponseSerializesJsonSerializableDto(): void
    {
        $key = 'test-key';
        $dto = new BookReadDto(
            id: 42,
            title: 'Test Book',
            year: 2024,
            description: 'A test book',
            isbn: '978-3-16-148410-0',
            authorIds: [1, 2],
            authorNames: [1 => 'Author One', 2 => 'Author Two'],
            coverUrl: '/covers/test.jpg',
            status: BookStatus::Published->value,
            version: 1,
        );
        $ttl = 3600;

        $expectedData = [
            'id' => 42,
            'title' => 'Test Book',
            'year' => 2024,
            'description' => 'A test book',
            'isbn' => '978-3-16-148410-0',
            'authorIds' => [1, 2],
            'authorNames' => [1 => 'Author One', 2 => 'Author Two'],
            'coverUrl' => '/covers/test.jpg',
            'isPublished' => true,
            'status' => BookStatus::Published->value,
            'version' => 1,
        ];

        $this->repository->expects($this->once())
            ->method('saveResponse')
            ->with($key, 201, (string)json_encode($expectedData), $ttl);

        $this->service->saveResponse($key, 201, $dto, null, $ttl);
    }

    public function testSaveResponseReturnsEmptyArrayForNonSerializableObjects(): void
    {
        $key = 'test-key';
        $ttl = 3600;

        $this->repository->expects($this->once())
            ->method('saveResponse')
            ->with($key, 200, (string)json_encode([]), $ttl);

        $this->service->saveResponse($key, 200, new \stdClass(), null, $ttl);
    }

    public function testAcquireLockDelegatesToMutexWithPrefix(): void
    {
        $key = 'test-key';
        $timeout = 5;

        $this->mutex->expects($this->once())
            ->method('acquire')
            ->with('idempotency:test-key', $timeout)
            ->willReturn(true);

        $result = $this->service->acquireLock($key, $timeout);

        $this->assertTrue($result);
    }

    public function testAcquireLockReturnsFalseWhenMutexFails(): void
    {
        $key = 'test-key';

        $this->mutex->expects($this->once())
            ->method('acquire')
            ->with('idempotency:test-key', 0)
            ->willReturn(false);

        $result = $this->service->acquireLock($key);

        $this->assertFalse($result);
    }

    public function testReleaseLockDelegatesToMutexWithPrefix(): void
    {
        $key = 'test-key';

        $this->mutex->expects($this->once())
            ->method('release')
            ->with('idempotency:test-key');

        $this->service->releaseLock($key);
    }
}
