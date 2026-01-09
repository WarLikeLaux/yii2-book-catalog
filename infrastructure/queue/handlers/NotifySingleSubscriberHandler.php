<?php

declare(strict_types=1);

namespace app\infrastructure\queue\handlers;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\application\ports\SmsSenderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class NotifySingleSubscriberHandler
{
    private const string HASH_ALGORITHM = 'sha256';

    public function __construct(
        private SmsSenderInterface $sender,
        private AsyncIdempotencyStorageInterface $idempotencyStorage,
        private LoggerInterface $logger,
        private string $phoneHashKey,
    ) {
    }

    public function handle(string $phone, string $message, int $bookId): void
    {
        $phoneHash = hash_hmac(self::HASH_ALGORITHM, $phone, $this->phoneHashKey);
        $idempotencyKey = sprintf('sms:%d:%s', $bookId, $phoneHash);

        if (!$this->idempotencyStorage->acquire($idempotencyKey)) {
            $this->logger->info('Skipping duplicate SMS notification', [
                'phone' => $this->maskPhone($phone),
                'book_id' => $bookId,
                'idempotency_key' => $idempotencyKey,
            ]);
            return;
        }

        try {
            $this->sender->send($phone, $message);

            $this->logger->info('SMS notification sent successfully', [
                'phone' => $this->maskPhone($phone),
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
                'phone' => $this->maskPhone($phone),
                'book_id' => $bookId,
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
    }

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($phone, 0, 2) . str_repeat('*', $len - 4) . substr($phone, -2);
    }
}
