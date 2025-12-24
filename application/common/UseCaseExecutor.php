<?php

declare(strict_types=1);

namespace app\application\common;

use app\application\ports\NotificationInterface;
use app\application\ports\TranslatorInterface;
use app\domain\exceptions\DomainException;
use Psr\Log\LoggerInterface;

final class UseCaseExecutor
{
    public function __construct(
        private readonly NotificationInterface $notifier,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function execute(callable $useCase, string $successMessage, array $logContext = []): bool
    {
        try {
            $useCase();
            $this->notifier->success($successMessage);
            return true;
        } catch (DomainException $e) {
            $this->notifier->error($e->getMessage());
            return false;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            $this->notifier->error($this->translator->translate('app', 'error.unexpected'));
            return false;
        }
    }

    public function executeForApi(callable $useCase, string $successMessage, array $logContext = []): array
    {
        try {
            $useCase();
            return ['success' => true, 'message' => $successMessage];
        } catch (DomainException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            return ['success' => false, 'message' => $this->translator->translate('app', 'error.unexpected')];
        }
    }

    public function query(callable $query, mixed $fallback, string $errorMessage, array $logContext = []): mixed
    {
        try {
            return $query();
        } catch (DomainException $e) {
            $this->notifier->error($e->getMessage());
            return $fallback;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            $this->notifier->error($errorMessage);
            return $fallback;
        }
    }
}
