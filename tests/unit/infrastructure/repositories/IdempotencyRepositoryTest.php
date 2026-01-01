<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\infrastructure\persistence\IdempotencyKey;
use app\infrastructure\repositories\IdempotencyRepository;
use Codeception\Test\Unit;
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

        $response = $this->repository->getResponse($key);

        $this->assertNotNull($response);
        $this->assertSame(200, $response['status_code']);
        $this->assertSame('{"result": "ok"}', $response['body']);
    }

    public function testGetExpiredResponseReturnsNull(): void
    {
        $key = 'expired-key';
        $this->repository->saveResponse($key, 200, '{"result": "ok"}', -10);

        $response = $this->repository->getResponse($key);

        $this->assertNull($response);
    }

    public function testGetNonExistentKeyReturnsNull(): void
    {
        $this->assertNull($this->repository->getResponse('non-existent'));
    }

    public function testSaveDuplicateKeyDoesNotCrash(): void
    {
        $key = 'duplicate-key';
        $this->repository->saveResponse($key, 200, 'first', 3600);

        $this->repository->saveResponse($key, 200, 'second', 3600);

        $response = $this->repository->getResponse($key);
        $this->assertSame('first', $response['body']);
    }
}
