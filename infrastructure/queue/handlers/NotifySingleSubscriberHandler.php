<?php

declare(strict_types=1);

namespace app\infrastructure\queue\handlers;

use app\application\ports\CacheInterface;
use app\application\ports\SmsSenderInterface;
use app\infrastructure\services\LogCategory;
use app\infrastructure\services\YiiPsrLogger;
use Throwable;

final readonly class NotifySingleSubscriberHandler
{
    private const int IDEMPOTENCY_TTL = 86400;

    public function __construct(
        private SmsSenderInterface $sender,
        private CacheInterface $cache,
    ) {
    }

    public function handle(string $phone, string $message, int $bookId): void
    {
        $idempotencyKey = sprintf('sms_handled:%d:%s', $bookId, md5($phone));
        $token = bin2hex(random_bytes(8));
        $storedToken = $this->cache->getOrSet(
            $idempotencyKey,
            static fn(): string => $token,
            self::IDEMPOTENCY_TTL,
        );

        if ($storedToken !== $token) {
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
            $this->cache->delete($idempotencyKey);

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
