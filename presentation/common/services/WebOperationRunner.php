<?php

declare(strict_types=1);

namespace app\presentation\common\services;

use app\application\common\exceptions\ApplicationException;
use app\application\common\pipeline\PipelineFactory;
use app\application\ports\CommandInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\TranslatorInterface;
use app\application\ports\UseCaseInterface;
use app\presentation\common\dto\ApiResponse;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class WebOperationRunner
{
    public function __construct(
        private NotificationInterface $notifier,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private PipelineFactory $pipelineFactory,
    ) {
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @param array<string, mixed> $logContext
     * @return T|null
     */
    public function runStep(callable $operation, string $logMsg, array $logContext = []): mixed
    {
        try {
            return $operation();
        } catch (Throwable $e) {
            $this->logger->error($logMsg, array_merge($logContext, ['exception' => $e]));
            return null;
        }
    }

    /**
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     * @param array<string, mixed> $logContext
     */
    public function execute(
        CommandInterface $command,
        UseCaseInterface $useCase,
        string $successMessage,
        array $logContext = [],
    ): mixed {
        try {
            /** @var TResponse $result */
            $result = $this->pipelineFactory->createDefault()->execute($command, $useCase);
            $this->notifier->success($successMessage);
            return $result;
        } catch (ApplicationException $e) {
            $this->notifier->error($this->translator->translate('app', $e->getMessage()));
            return null;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            $this->notifier->error($this->translator->translate('app', 'error.unexpected'));
            return null;
        }
    }

    /**
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     * @param array<string, mixed> $logContext
     */
    public function executeForApi(
        CommandInterface $command,
        UseCaseInterface $useCase,
        string $successMessage,
        array $logContext = [],
    ): ApiResponse {
        try {
            /** @var TResponse $result */
            $result = $this->pipelineFactory->createDefault()->execute($command, $useCase);
            return ApiResponse::success($successMessage, $result);
        } catch (ApplicationException $e) {
            return ApiResponse::failure($this->translator->translate('app', $e->getMessage()));
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            return ApiResponse::failure($this->translator->translate('app', 'error.unexpected'));
        }
    }

    /**
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     * @param callable(ApplicationException): void $onDomainError
     * @param (callable(): void)|null $onError
     * @return TResponse|null
     */
    public function executeWithFormErrors(
        CommandInterface $command,
        UseCaseInterface $useCase,
        string $successMessage,
        callable $onDomainError,
        callable|null $onError = null,
    ): mixed {
        try {
            /** @var TResponse $result */
            $result = $this->pipelineFactory->createDefault()->execute($command, $useCase);
            $this->notifier->success($successMessage);
            return $result;
        } catch (ApplicationException $e) {
            if ($onError !== null) {
                $onError();
            }

            $onDomainError($e);
            return null;
        } catch (Throwable $e) {
            if ($onError !== null) {
                $onError();
            }

            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $this->notifier->error($this->translator->translate('app', 'error.unexpected'));
            return null;
        }
    }

    /**
     * @template TResponse
     * @param callable(): TResponse $query
     * @param TResponse $fallback
     * @param array<string, mixed> $logContext
     * @return TResponse
     */
    public function query(callable $query, mixed $fallback, string $errorMessage, array $logContext = []): mixed
    {
        try {
            return $query();
        } catch (ApplicationException $e) {
            $this->notifier->error($this->translator->translate('app', $e->getMessage()));
            return $fallback;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            $this->notifier->error($errorMessage);
            return $fallback;
        }
    }

    /**
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     */
    public function executeAndPropagate(
        CommandInterface $command,
        UseCaseInterface $useCase,
        string $successMessage,
    ): mixed {
        /** @var TResponse $result */
        $result = $this->pipelineFactory->createDefault()->execute($command, $useCase);
        $this->notifier->success($successMessage);
        return $result;
    }
}
