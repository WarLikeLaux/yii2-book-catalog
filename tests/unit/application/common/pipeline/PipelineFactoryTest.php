<?php

declare(strict_types=1);

namespace tests\unit\application\common\pipeline;

use app\application\common\middleware\DomainExceptionTranslationMiddleware;
use app\application\common\pipeline\PipelineFactory;
use app\application\ports\PipelineInterface;
use app\application\ports\TracerInterface;
use app\application\ports\TransactionInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class PipelineFactoryTest extends TestCase
{
    private TracerInterface&Stub $tracer;
    private TransactionInterface&Stub $transaction;
    private DomainExceptionTranslationMiddleware&Stub $exceptionTranslationMiddleware;
    private PipelineFactory $factory;

    protected function setUp(): void
    {
        $this->tracer = $this->createStub(TracerInterface::class);
        $this->transaction = $this->createStub(TransactionInterface::class);
        $this->exceptionTranslationMiddleware = $this->createStub(DomainExceptionTranslationMiddleware::class);

        $this->factory = new PipelineFactory(
            $this->tracer,
            $this->transaction,
            $this->exceptionTranslationMiddleware,
        );
    }

    public function testCreateDefaultReturnsPipeline(): void
    {
        $pipeline = $this->factory->createDefault();

        $this->assertInstanceOf(PipelineInterface::class, $pipeline);
    }

    public function testCreateDefaultReturnsNewInstanceEachTime(): void
    {
        $pipeline1 = $this->factory->createDefault();
        $pipeline2 = $this->factory->createDefault();

        $this->assertNotSame($pipeline1, $pipeline2);
    }
}
