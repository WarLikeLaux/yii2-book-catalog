<?php

declare(strict_types=1);

namespace app\infrastructure\queue\handlers;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\application\ports\SmsSenderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class NotifySingleSubscriberHandler
{
    public function __construct(
        private SmsSenderInterface $sender,
        private AsyncIdempotencyStorageInterface $idempotencyStorage,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(string $phone, string $message, int $bookId): void
    {
        $phoneHash = hash('sha256', $phone);
        $idempotencyKey = sprintf('sms:%d:%s', $bookId, $phoneHash);

        if (!$this->idempotencyStorage->acquire($idempotencyKey)) {
            $this->logger->info('Skipping duplicate SMS notification', [
                'phone' => $phone,
                'book_id' => $bookId,
                'idempotency_key' => $idempotencyKey,
            ]);
            return;
        }

        try {
            $this->sender->send($phone, $message);

            $this->logger->info('SMS notification sent successfully', [
                'phone' => $phone,
                'book_id' => $bookId,
            ]);
        } catch (Throwable $exception) {
            try {
                $this->idempotencyStorage->release($idempotencyKey);
            } catch (Throwable $releaseException) {
                $this->logger->error('Failed to release idempotency key', [
                    'key' => $idempotencyKey,
                    'error' => $releaseException->getMessage(),
                ]);
            }

            $this->logger->error('SMS notification failed', [
                'phone' => $phone,
                'book_id' => $bookId,
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
