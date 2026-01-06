<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\services;

use app\application\common\pipeline\PipelineFactory;
use app\application\ports\CommandInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\PipelineInterface;
use app\application\ports\TranslatorInterface;
use app\application\ports\UseCaseInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\presentation\common\services\WebUseCaseRunner;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class WebUseCaseRunnerQueryTest extends Unit
{
    private NotificationInterface&MockObject $notifier;
    private LoggerInterface&MockObject $logger;
    private TranslatorInterface&MockObject $translator;
    private PipelineFactory&MockObject $pipelineFactory;
    private WebUseCaseRunner $runner;

    protected function _before(): void
    {
        $this->notifier = $this->createMock(NotificationInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->pipelineFactory = $this->createMock(PipelineFactory::class);
        $this->runner = new WebUseCaseRunner($this->notifier, $this->logger, $this->translator, $this->pipelineFactory);
    }

    public function testQueryReturnsResult(): void
    {
        $result = $this->runner->query(static fn() => 'data', 'fallback', 'error message');
        $this->assertSame('data', $result);
    }

    public function testQueryReturnsFallbackOnDomainException(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', DomainErrorCode::BookTitleEmpty->value)
            ->willReturn(DomainErrorCode::BookTitleEmpty->value);
        $this->notifier->expects($this->once())->method('error')->with(DomainErrorCode::BookTitleEmpty->value);

        $result = $this->runner->query(
            static fn() => throw new ValidationException(DomainErrorCode::BookTitleEmpty),
            'fallback',
            'error message',
        );

        $this->assertSame('fallback', $result);
    }

    public function testQueryReturnsFallbackOnException(): void
    {
        $this->logger->expects($this->once())->method('error');
        $this->notifier->expects($this->once())->method('error')->with('generic error');

        $result = $this->runner->query(
            static fn() => throw new RuntimeException('boom'),
            'fallback',
            'generic error',
        );

        $this->assertSame('fallback', $result);
    }

    public function testExecuteForApiReturnsDomainError(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())
            ->method('execute')
            ->willThrowException(new ValidationException(DomainErrorCode::BookTitleEmpty));

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', DomainErrorCode::BookTitleEmpty->value)
            ->willReturn(DomainErrorCode::BookTitleEmpty->value);

        $result = $this->runner->executeForApi(
            $command,
            $useCase,
            'success',
        );

        $this->assertFalse($result['success']);
        $this->assertSame(DomainErrorCode::BookTitleEmpty->value, $result['message']);
    }

    public function testExecuteForApiReturnsGenericError(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())
            ->method('execute')
            ->willThrowException(new RuntimeException('boom'));

        $this->translator->expects($this->once())
            ->method('translate')
            ->willReturn('error.unexpected');
        $this->logger->expects($this->once())->method('error');

        $result = $this->runner->executeForApi(
            $command,
            $useCase,
            'success',
        );

        $this->assertFalse($result['success']);
        $this->assertSame('error.unexpected', $result['message']);
    }
}
