<?php

declare(strict_types=1);

namespace app\application\common;

use app\domain\exceptions\DomainException;
use app\interfaces\NotificationInterface;
use Psr\Log\LoggerInterface;
use Yii;

final class UseCaseExecutor
{
    public function __construct(
        private readonly NotificationInterface $notifier,
        private readonly LoggerInterface $logger
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
            $this->notifier->error(Yii::t('app', 'Unexpected error occurred. Please contact administrator.'));
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
            return ['success' => false, 'message' => Yii::t('app', 'Unexpected error occurred. Please contact administrator.')];
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
