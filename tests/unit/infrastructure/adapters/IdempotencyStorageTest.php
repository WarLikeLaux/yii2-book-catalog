<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\application\common\IdempotencyKeyStatus;
use app\infrastructure\adapters\IdempotencyStorage;
use app\infrastructure\adapters\StreamGetContentsStub;
use app\infrastructure\adapters\SystemClock;
use app\infrastructure\persistence\IdempotencyKey;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class IdempotencyStorageTest extends Unit
{
    private IdempotencyStorage $storage;

    protected function _before(): void
    {
        $this->storage = new IdempotencyStorage(new NullLogger(), new SystemClock());
        IdempotencyKey::deleteAll();
    }

    public function testSaveAndGetResponse(): void
    {
        $key = 'test-key';
        $this->storage->saveResponse($key, 200, '{"result": "ok"}', 3600);

        $response = $this->storage->getRecord($key);

        $this->assertNotNull($response);
        $this->assertSame(IdempotencyKeyStatus::Finished, $response->status);
        $this->assertSame(200, $response->statusCode);
        $this->assertSame(['result' => 'ok'], $response->data);
    }

    public function testGetExpiredResponseReturnsNull(): void
    {
        $key = 'expired-key';
        $this->storage->saveResponse($key, 200, '{"result": "ok"}', -10);

        $response = $this->storage->getRecord($key);

        $this->assertNull($response);
    }

    public function testGetNonExistentKeyReturnsNull(): void
    {
        $this->assertNull($this->storage->getRecord('non-existent'));
    }

    public function testGetRecordReturnsNullWhenStatusIsInvalid(): void
    {
        $key = 'invalid-status-key';
        $this->storage->saveResponse($key, 200, '{"result": "ok"}', 3600);
        IdempotencyKey::updateAll(['status' => 'unknown'], ['idempotency_key' => $key]);

        $response = $this->storage->getRecord($key);

        $this->assertNull($response);
    }

    public function testSaveStartedAndGetRecord(): void
    {
        $key = 'started-key';
        $this->storage->saveStarted($key, 3600);

        $response = $this->storage->getRecord($key);

        $this->assertNotNull($response);
        $this->assertSame(IdempotencyKeyStatus::Started, $response->status);
        $this->assertNull($response->statusCode);
        $this->assertSame([], $response->data);
    }

    public function testSaveStartedAllowsMaxKeyLength(): void
    {
        $key = str_repeat('m', IdempotencyKey::MAX_KEY_LENGTH);
        $this->assertTrue($this->storage->saveStarted($key, 3600));

        $response = $this->storage->getRecord($key);
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

        $storage = new IdempotencyStorage($logger, new SystemClock());
        $key = str_repeat('a', IdempotencyKey::MAX_KEY_LENGTH + 1);

        $this->assertFalse($storage->saveStarted($key, 3600));
        $this->assertNull($storage->getRecord($key));
    }

    public function testSaveDuplicateKeyDoesNotCrash(): void
    {
        $key = 'duplicate-key';
        $this->storage->saveResponse($key, 200, '{"result":"first"}', 3600);

        $this->storage->saveResponse($key, 200, '{"result":"second"}', 3600);

        $response = $this->storage->getRecord($key);
        $this->assertNotNull($response);
        $this->assertSame(['result' => 'first'], $response->data);
    }

    public function testSaveResponseLogsErrorOnValidationFailure(): void
    {
        IdempotencyKey::deleteAll();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error');

        $storage = new IdempotencyStorage($logger, new SystemClock());
        $key = str_repeat('b', IdempotencyKey::MAX_KEY_LENGTH + 1);

        $storage->saveResponse($key, 200, '{"result": "ok"}', 3600);

        $this->assertNull($storage->getRecord($key));
    }

    public function testSaveResponseAllowsMaxKeyLength(): void
    {
        $key = str_repeat('n', IdempotencyKey::MAX_KEY_LENGTH);
        $this->storage->saveResponse($key, 200, '{"result": "ok"}', 3600);

        $response = $this->storage->getRecord($key);
        $this->assertNotNull($response);
        $this->assertSame(IdempotencyKeyStatus::Finished, $response->status);
        $this->assertSame(200, $response->statusCode);
        $this->assertSame(['result' => 'ok'], $response->data);
    }

    public function testNormalizeResponseBodyReturnsNullForNonResource(): void
    {
        $result = $this->invokePrivateMethod($this->storage, 'normalizeResponseBody', [['payload']]);

        $this->assertNull($result);
    }

    public function testNormalizeResponseBodyReturnsNullWhenStreamReadFails(): void
    {
        $resource = fopen('php://temp', 'r+');
        $this->assertIsResource($resource);

        try {
            StreamGetContentsStub::$forceFalse = true;
            $result = $this->invokePrivateMethod($this->storage, 'normalizeResponseBody', [$resource]);
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

        $result = $this->invokePrivateMethod($this->storage, 'normalizeResponseBody', [$resource]);

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
