<?php

declare(strict_types=1);

namespace tests\unit\application\common\pipeline;

use app\application\common\IdempotencyServiceInterface;
use app\application\common\pipeline\PipelineFactory;
use app\application\ports\FileStorageInterface;
use app\application\ports\PipelineInterface;
use app\application\ports\TracerInterface;
use app\application\ports\TransactionInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class PipelineFactoryTest extends Unit
{
    private TracerInterface&MockObject $tracer;
    private TransactionInterface&MockObject $transaction;
    private FileStorageInterface&MockObject $fileStorage;
    private IdempotencyServiceInterface&MockObject $idempotencyService;
    private PipelineFactory $factory;

    protected function _before(): void
    {
        $this->tracer = $this->createMock(TracerInterface::class);
        $this->transaction = $this->createMock(TransactionInterface::class);
        $this->fileStorage = $this->createMock(FileStorageInterface::class);
        $this->idempotencyService = $this->createMock(IdempotencyServiceInterface::class);

        $this->factory = new PipelineFactory(
            $this->tracer,
            $this->transaction,
            $this->fileStorage,
            $this->idempotencyService,
        );
    }

    public function testCreateDefaultReturnsPipeline(): void
    {
        $pipeline = $this->factory->createDefault();

        $this->assertInstanceOf(PipelineInterface::class, $pipeline);
    }

    public function testCreateWithFileLifecycleReturnsPipeline(): void
    {
        $pipeline = $this->factory->createWithFileLifecycle();

        $this->assertInstanceOf(PipelineInterface::class, $pipeline);
    }

    public function testCreateWithoutIdempotencyReturnsPipeline(): void
    {
        $pipeline = $this->factory->createWithoutIdempotency();

        $this->assertInstanceOf(PipelineInterface::class, $pipeline);
    }

    public function testCreateWithFileLifecycleWithoutIdempotencyReturnsPipeline(): void
    {
        $pipeline = $this->factory->createWithFileLifecycleWithoutIdempotency();

        $this->assertInstanceOf(PipelineInterface::class, $pipeline);
    }

    public function testCreateDefaultReturnsNewInstanceEachTime(): void
    {
        $pipeline1 = $this->factory->createDefault();
        $pipeline2 = $this->factory->createDefault();

        $this->assertNotSame($pipeline1, $pipeline2);
    }
}
