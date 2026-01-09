<?php

declare(strict_types=1);

namespace app\infrastructure\queue\handlers;

use app\application\ports\AsyncIdempotencyStorageInterface;
use app\application\ports\SmsSenderInterface;
use app\infrastructure\services\LogCategory;
use app\infrastructure\services\YiiPsrLogger;
use Throwable;

final readonly class NotifySingleSubscriberHandler
{
    public function __construct(
        private SmsSenderInterface $sender,
        private AsyncIdempotencyStorageInterface $idempotencyStorage,
    ) {
    }

    public function handle(string $phone, string $message, int $bookId): void
    {
        $phoneHash = substr(hash('sha256', $phone), 0, 16);
        $idempotencyKey = sprintf('sms:%d:%s', $bookId, $phoneHash);

        if (!$this->idempotencyStorage->acquire($idempotencyKey)) {
            (new YiiPsrLogger(LogCategory::SMS))->info('Skipping duplicate SMS notification', [
                'phone' => $phone,
                'book_id' => $bookId,
                'idempotency_key' => $idempotencyKey,
            ]);
            return;
        }

        $logger = new YiiPsrLogger(LogCategory::SMS);

        try {
            $this->sender->send($phone, $message);

            $logger->info('SMS notification sent successfully', [
                'phone' => $phone,
                'book_id' => $bookId,
            ]);
        } catch (Throwable $exception) {
            try {
                $this->idempotencyStorage->release($idempotencyKey);
            } catch (Throwable $releaseException) {
                $logger->error('Failed to release idempotency key', [
                    'key' => $idempotencyKey,
                    'error' => $releaseException->getMessage(),
                ]);
            }

            $logger->error('SMS notification failed', [
                'phone' => $phone,
                'book_id' => $bookId,
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
