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
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class WebOperationRunnerTest extends Unit
{
    private const ERROR_TITLE_EMPTY = 'book.error.title_empty';
    private const ERROR_UNEXPECTED = 'error.unexpected';
    private NotificationInterface&MockObject $notifier;
    private LoggerInterface&MockObject $logger;
    private TranslatorInterface&MockObject $translator;
    private PipelineFactory&MockObject $pipelineFactory;
    private WebOperationRunner $runner;

    protected function _before(): void
    {
        $this->notifier = $this->createMock(NotificationInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->pipelineFactory = $this->createMock(PipelineFactory::class);
        $this->runner = new WebOperationRunner($this->notifier, $this->logger, $this->translator, $this->pipelineFactory);
    }

    public function testExecuteSuccessNotifiesAndReturnsTrue(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())->method('execute')->with($command, $useCase)->willReturn('success-result');

        $this->notifier->expects($this->once())->method('success')->with('ok');
        $this->notifier->expects($this->never())->method('error');

        $result = $this->runner->execute($command, $useCase, 'ok');

        $this->assertSame('success-result', $result);
    }

    public function testExecuteHandlesApplicationException(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())
            ->method('execute')
            ->willThrowException(new ApplicationException(self::ERROR_TITLE_EMPTY));

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', self::ERROR_TITLE_EMPTY)
            ->willReturn(self::ERROR_TITLE_EMPTY);
        $this->notifier->expects($this->once())
            ->method('error')
            ->with(self::ERROR_TITLE_EMPTY);
        $this->notifier->expects($this->never())->method('success');

        $result = $this->runner->execute($command, $useCase, 'ok');

        $this->assertNull($result);
    }

    public function testExecuteHandlesUnexpectedException(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('boom'));

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', self::ERROR_UNEXPECTED, [])
            ->willReturn(self::ERROR_UNEXPECTED);
        $this->notifier->expects($this->once())->method('error')->with(self::ERROR_UNEXPECTED);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('boom', $this->callback(static fn($context) => $context['foo'] === 'bar'));

        $result = $this->runner->execute($command, $useCase, 'ok', ['foo' => 'bar']);

        $this->assertNull($result);
    }

    public function testExecuteForApiReturnsSuccessPayload(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())->method('execute')->willReturn('result');

        $result = $this->runner->executeForApi($command, $useCase, 'done');

        $this->assertInstanceOf(ApiResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('done', $result->message);
        $this->assertSame('result', $result->data);
    }

    public function testExecuteForApiReturnsFieldErrorsWhenApplicationExceptionHasField(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);
        $exception = new ApplicationException('subscription.error.invalid_author_id', 0, null, 'authorId');

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())->method('execute')->willThrowException($exception);

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', 'subscription.error.invalid_author_id')
            ->willReturn('Invalid author');

        $result = $this->runner->executeForApi($command, $useCase, 'ok');

        $this->assertInstanceOf(ApiResponse::class, $result);
        $this->assertFalse($result->success);
        $this->assertSame('Invalid author', $result->message);
        $this->assertSame(['authorId' => ['Invalid author']], $result->errors);
    }

    public function testExecuteForApiReturnsEmptyErrorsWhenApplicationExceptionHasNoField(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);
        $exception = new ApplicationException(self::ERROR_TITLE_EMPTY);

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())->method('execute')->willThrowException($exception);

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', self::ERROR_TITLE_EMPTY)
            ->willReturn(self::ERROR_TITLE_EMPTY);

        $result = $this->runner->executeForApi($command, $useCase, 'ok');

        $this->assertInstanceOf(ApiResponse::class, $result);
        $this->assertFalse($result->success);
        $this->assertSame(self::ERROR_TITLE_EMPTY, $result->message);
        $this->assertSame([], $result->errors);
    }

    public function testExecuteForApiHandlesUnexpectedExceptionWithLogging(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);
        $exception = new \RuntimeException('api boom');

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())->method('execute')->willThrowException($exception);

        $this->translator->method('translate')->willReturn('error.unexpected');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('api boom', $this->callback(static fn($c) => $c['requestId'] === '123'));

        $result = $this->runner->executeForApi($command, $useCase, 'ok', ['requestId' => '123']);

        $this->assertInstanceOf(ApiResponse::class, $result);
        $this->assertFalse($result->success);
        $this->assertSame('error.unexpected', $result->message);
    }

    public function testExecuteWithFormErrorsReturnsResultOnSuccess(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())->method('execute')->willReturn(42);

        $this->notifier->expects($this->once())->method('success')->with('created');

        $result = $this->runner->executeWithFormErrors(
            $command,
            $useCase,
            'created',
            static fn() => null,
        );

        $this->assertSame(42, $result);
    }

    public function testExecuteWithFormErrorsCallsOnApplicationErrorCallback(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);
        $exception = new ApplicationException(self::ERROR_TITLE_EMPTY);

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())->method('execute')->willThrowException($exception);

        $onErrorCalled = false;
        $capturedException = null;

        $result = $this->runner->executeWithFormErrors(
            $command,
            $useCase,
            'ok',
            static function (ApplicationException $e) use (&$capturedException) {
                $capturedException = $e;
            },
            static function () use (&$onErrorCalled) {
                $onErrorCalled = true;
            },
        );

        $this->assertNull($result);
        $this->assertSame($exception, $capturedException);
        $this->assertTrue($onErrorCalled);
    }

    public function testExecuteWithFormErrorsHandlesUnexpectedException(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $useCase = $this->createMock(UseCaseInterface::class);
        $pipeline = $this->createMock(PipelineInterface::class);
        $exception = new \RuntimeException('unexpected error');

        $this->pipelineFactory->expects($this->once())->method('createDefault')->willReturn($pipeline);
        $pipeline->expects($this->once())->method('execute')->willThrowException($exception);

        $onErrorCalled = false;
        $onError = static function () use (&$onErrorCalled) {
            $onErrorCalled = true;
        };

        $this->logger->expects($this->once())
            ->method('error')
            ->with('unexpected error', ['exception' => $exception]);

        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', self::ERROR_UNEXPECTED)
            ->willReturn('An unexpected error occurred');

        $this->notifier->expects($this->once())
            ->method('error')
            ->with('An unexpected error occurred');

        $result = $this->runner->executeWithFormErrors(
            $command,
            $useCase,
            'ok',
            static fn() => null,
            $onError,
        );

        $this->assertNull($result);
        $this->assertTrue($onErrorCalled);
    }

    public function testRunStepReturnsResultOnSuccess(): void
    {
        $result = $this->runner->runStep(
            static fn() => 'step-result',
            'error message',
        );

        $this->assertSame('step-result', $result);
    }

    public function testRunStepLogsAndReturnsNullOnException(): void
    {
        $exception = new \RuntimeException('step failed');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('error message', ['foo' => 'bar', 'exception' => $exception]);

        $result = $this->runner->runStep(
            static fn() => throw $exception,
            'error message',
            ['foo' => 'bar'],
        );

        $this->assertNull($result);
    }
}
