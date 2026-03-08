<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\services;

use app\application\common\exceptions\ApplicationException;
use app\application\common\pipeline\PipelineFactory;
use app\application\ports\CommandInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\PipelineInterface;
use app\application\ports\TranslatorInterface;
use app\application\ports\UseCaseInterface;
use app\presentation\common\dto\ApiResponse;
use app\presentation\common\services\WebOperationRunner;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class WebOperationRunnerQueryTest extends TestCase
{
    private NotificationInterface&Stub $notifier;
    private LoggerInterface&Stub $logger;
    private TranslatorInterface&Stub $translator;
    private PipelineFactory&Stub $pipelineFactory;
    private WebOperationRunner $runner;

    protected function setUp(): void
    {
        $this->notifier = $this->createStub(NotificationInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->translator = $this->createStub(TranslatorInterface::class);
        $this->pipelineFactory = $this->createStub(PipelineFactory::class);
        $this->runner = new WebOperationRunner($this->notifier, $this->logger, $this->translator, $this->pipelineFactory);
    }

    public function testQueryReturnsResult(): void
    {
        $result = $this->runner->query(static fn() => 'data', 'fallback', 'error message');
        $this->assertSame('data', $result);
    }

    public function testQueryReturnsFallbackOnApplicationException(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $notifier = $this->createMock(NotificationInterface::class);
        $runner = new WebOperationRunner($notifier, $this->logger, $translator, $this->pipelineFactory);

        $translator->expects($this->once())
            ->method('translate')
            ->with('app', 'book.error.title_empty')
            ->willReturn('book.error.title_empty');
        $notifier->expects($this->once())->method('error')->with('book.error.title_empty');

        $result = $runner->query(
            static fn() => throw new ApplicationException('book.error.title_empty'),
            'fallback',
            'error message',
        );

        $this->assertSame('fallback', $result);
    }

    public function testQueryReturnsFallbackOnException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $notifier = $this->createMock(NotificationInterface::class);
        $runner = new WebOperationRunner($notifier, $logger, $this->translator, $this->pipelineFactory);

        $logger->expects($this->once())->method('error');
        $notifier->expects($this->once())->method('error')->with('generic error');

        $result = $runner->query(
            static fn() => throw new RuntimeException('boom'),
            'fallback',
            'generic error',
        );

        $this->assertSame('fallback', $result);
    }

    public function testExecuteForApiReturnsApplicationError(): void
    {
        $command = $this->createStub(CommandInterface::class);
        $useCase = $this->createStub(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipelineFactory = $this->createMock(PipelineFactory::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $runner = new WebOperationRunner($this->notifier, $this->logger, $translator, $pipelineFactory);

        $pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())
            ->method('execute')
            ->willThrowException(new ApplicationException('book.error.title_empty'));

        $translator->expects($this->once())
            ->method('translate')
            ->with('app', 'book.error.title_empty')
            ->willReturn('book.error.title_empty');

        $result = $runner->executeForApi(
            $command,
            $useCase,
            'success',
        );

        $this->assertInstanceOf(ApiResponse::class, $result);
        $this->assertFalse($result->success);
        $this->assertSame('book.error.title_empty', $result->message);
    }

    public function testExecuteForApiReturnsGenericError(): void
    {
        $command = $this->createStub(CommandInterface::class);
        $useCase = $this->createStub(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipelineFactory = $this->createMock(PipelineFactory::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $runner = new WebOperationRunner($this->notifier, $logger, $translator, $pipelineFactory);

        $pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())
            ->method('execute')
            ->willThrowException(new RuntimeException('boom'));

        $translator->expects($this->once())
            ->method('translate')
            ->willReturn('error.unexpected');
        $logger->expects($this->once())->method('error');

        $result = $runner->executeForApi(
            $command,
            $useCase,
            'success',
        );

        $this->assertInstanceOf(ApiResponse::class, $result);
        $this->assertFalse($result->success);
        $this->assertSame('error.unexpected', $result->message);
    }
}
