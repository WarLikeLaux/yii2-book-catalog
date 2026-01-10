<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\infrastructure\persistence\IdempotencyKey;
use app\infrastructure\repositories\IdempotencyRepository;
use app\infrastructure\repositories\StreamGetContentsStub;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class IdempotencyRepositoryTest extends Unit
{
    private IdempotencyRepository $repository;

    protected function _before(): void
    {
        $this->repository = new IdempotencyRepository(new NullLogger());
        IdempotencyKey::deleteAll();
    }

    public function testSaveAndGetResponse(): void
    {
        $key = 'test-key';
        $this->repository->saveResponse($key, 200, '{"result": "ok"}', 3600);

        $response = $this->repository->getRecord($key);

        $this->assertNotNull($response);
        $this->assertSame('finished', $response['status']);
        $this->assertSame(200, $response['status_code']);
        $this->assertSame('{"result": "ok"}', $response['body']);
    }

    public function testGetExpiredResponseReturnsNull(): void
    {
        $key = 'expired-key';
        $this->repository->saveResponse($key, 200, '{"result": "ok"}', -10);

        $response = $this->repository->getRecord($key);

        $this->assertNull($response);
    }

    public function testGetNonExistentKeyReturnsNull(): void
    {
        $this->assertNull($this->repository->getRecord('non-existent'));
    }

    public function testSaveStartedAndGetRecord(): void
    {
        $key = 'started-key';
        $this->repository->saveStarted($key, 3600);

        $response = $this->repository->getRecord($key);

        $this->assertNotNull($response);
        $this->assertSame('started', $response['status']);
        $this->assertNull($response['status_code']);
        $this->assertNull($response['body']);
    }

    public function testSaveStartedAllowsMaxKeyLength(): void
    {
        $key = str_repeat('m', 128);
        $this->assertTrue($this->repository->saveStarted($key, 3600));

        $response = $this->repository->getRecord($key);
        $this->assertNotNull($response);
    }

    public function testSaveStartedLogsErrorOnValidationFailure(): void
    {
        IdempotencyKey::deleteAll();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error');

        $repository = new IdempotencyRepository($logger);
        $key = str_repeat('a', 129);

        $this->assertFalse($repository->saveStarted($key, 3600));
    }

    public function testSaveDuplicateKeyDoesNotCrash(): void
    {
        $key = 'duplicate-key';
        $this->repository->saveResponse($key, 200, 'first', 3600);

        $this->repository->saveResponse($key, 200, 'second', 3600);

        $response = $this->repository->getRecord($key);
        $this->assertSame('first', $response['body']);
    }

    public function testSaveResponseLogsErrorOnValidationFailure(): void
    {
        IdempotencyKey::deleteAll();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error');

        $repository = new IdempotencyRepository($logger);
        $key = str_repeat('b', 129);

        $repository->saveResponse($key, 200, '{"result": "ok"}', 3600);
    }

    public function testSaveResponseAllowsMaxKeyLength(): void
    {
        $key = str_repeat('n', 128);
        $this->repository->saveResponse($key, 200, '{"result": "ok"}', 3600);

        $response = $this->repository->getRecord($key);
        $this->assertNotNull($response);
        $this->assertSame('{"result": "ok"}', $response['body']);
    }

    public function testNormalizeResponseBodyReturnsNullForNonResource(): void
    {
        $result = $this->invokePrivateMethod($this->repository, 'normalizeResponseBody', [['payload']]);

        $this->assertNull($result);
    }

    public function testNormalizeResponseBodyReturnsNullWhenStreamReadFails(): void
    {
        $resource = fopen('php://temp', 'r+');
        $this->assertIsResource($resource);

        try {
            StreamGetContentsStub::$forceFalse = true;
            $result = $this->invokePrivateMethod($this->repository, 'normalizeResponseBody', [$resource]);
            $this->assertNull($result);
        } finally {
            StreamGetContentsStub::$forceFalse = false;
            fclose($resource);
        }
    }

    public function testNormalizeResponseBodyReturnsContentForStream(): void
    {
        $resource = fopen('php://temp', 'r+');
        $this->assertIsResource($resource);

        fwrite($resource, 'payload');
        rewind($resource);

        $result = $this->invokePrivateMethod($this->repository, 'normalizeResponseBody', [$resource]);

        $this->assertSame('payload', $result);

        fclose($resource);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivateMethod(object $target, string $method, array $arguments): mixed
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }
}
