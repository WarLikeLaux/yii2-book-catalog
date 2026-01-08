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
    /**
     * Create a new handler with the SMS sender and idempotency storage dependencies.
     *
     * @param SmsSenderInterface $sender Service used to send SMS messages.
     * @param AsyncIdempotencyStorageInterface $idempotencyStorage Storage used to acquire and release idempotency keys to ensure SMS are sent idempotently.
     */
    public function __construct(
        private SmsSenderInterface $sender,
        private AsyncIdempotencyStorageInterface $idempotencyStorage,
    ) {
    }

    /**
     * Send an SMS to a single subscriber while ensuring the operation is idempotent.
     *
     * Acquires an idempotency lock for the (bookId, phone) pair and returns immediately if the lock cannot be obtained.
     * If sending fails the lock is released and the original exception is rethrown.
     *
     * @param string $phone Recipient phone number.
     * @param string $message Message body to send.
     * @param int $bookId Identifier used to scope idempotency for this notification.
     *
     * @throws \Throwable Rethrows any exception thrown by the SMS sender after releasing the idempotency lock.
     */
    public function handle(string $phone, string $message, int $bookId): void
    {
        $idempotencyKey = sprintf('sms:%d:%s', $bookId, md5($phone));

        if (!$this->idempotencyStorage->acquire($idempotencyKey)) {
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
            $this->idempotencyStorage->release($idempotencyKey);

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