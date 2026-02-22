<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories;

use app\application\common\IdempotencyKeyStatus;
use app\infrastructure\adapters\SystemClock;
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
        $this->repository = new IdempotencyRepository(new NullLogger(), new SystemClock());
        IdempotencyKey::deleteAll();
    }

    public function testSaveAndGetResponse(): void
    {
        $key = 'test-key';
        $this->repository->saveResponse($key, 200, '{"result": "ok"}', 3600);

        $response = $this->repository->getRecord($key);

        $this->assertNotNull($response);
        $this->assertSame(IdempotencyKeyStatus::Finished, $response->status);
        $this->assertSame(200, $response->statusCode);
        $this->assertSame(['result' => 'ok'], $response->data);
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

    public function testGetRecordReturnsNullWhenStatusIsInvalid(): void
    {
        $key = 'invalid-status-key';
        $this->repository->saveResponse($key, 200, '{"result": "ok"}', 3600);
        IdempotencyKey::updateAll(['status' => 'unknown'], ['idempotency_key' => $key]);

        $response = $this->repository->getRecord($key);

        $this->assertNull($response);
    }

    public function testSaveStartedAndGetRecord(): void
    {
        $key = 'started-key';
        $this->repository->saveStarted($key, 3600);

        $response = $this->repository->getRecord($key);

        $this->assertNotNull($response);
        $this->assertSame(IdempotencyKeyStatus::Started, $response->status);
        $this->assertNull($response->statusCode);
        $this->assertSame([], $response->data);
    }

    public function testSaveStartedAllowsMaxKeyLength(): void
    {
        $key = str_repeat('m', IdempotencyKey::MAX_KEY_LENGTH);
        $this->assertTrue($this->repository->saveStarted($key, 3600));

        $response = $this->repository->getRecord($key);
        $this->assertNotNull($response);
        $this->assertSame(IdempotencyKeyStatus::Started, $response->status);
        $this->assertNull($response->statusCode);
        $this->assertSame([], $response->data);
    }

    public function testSaveStartedLogsErrorOnValidationFailure(): void
    {
        IdempotencyKey::deleteAll();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error');

        $repository = new IdempotencyRepository($logger, new SystemClock());
        $key = str_repeat('a', IdempotencyKey::MAX_KEY_LENGTH + 1);

        $this->assertFalse($repository->saveStarted($key, 3600));
        $this->assertNull($repository->getRecord($key));
    }

    public function testSaveDuplicateKeyDoesNotCrash(): void
    {
        $key = 'duplicate-key';
        $this->repository->saveResponse($key, 200, '{"result":"first"}', 3600);

        $this->repository->saveResponse($key, 200, '{"result":"second"}', 3600);

        $response = $this->repository->getRecord($key);
        $this->assertNotNull($response);
        $this->assertSame(['result' => 'first'], $response->data);
    }

    public function testSaveResponseLogsErrorOnValidationFailure(): void
    {
        IdempotencyKey::deleteAll();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error');

        $repository = new IdempotencyRepository($logger, new SystemClock());
        $key = str_repeat('b', IdempotencyKey::MAX_KEY_LENGTH + 1);

        $repository->saveResponse($key, 200, '{"result": "ok"}', 3600);

        $this->assertNull($repository->getRecord($key));
    }

    public function testSaveResponseAllowsMaxKeyLength(): void
    {
        $key = str_repeat('n', IdempotencyKey::MAX_KEY_LENGTH);
        $this->repository->saveResponse($key, 200, '{"result": "ok"}', 3600);

        $response = $this->repository->getRecord($key);
        $this->assertNotNull($response);
        $this->assertSame(IdempotencyKeyStatus::Finished, $response->status);
        $this->assertSame(200, $response->statusCode);
        $this->assertSame(['result' => 'ok'], $response->data);
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
