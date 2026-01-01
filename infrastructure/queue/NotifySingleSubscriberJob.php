<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use app\application\ports\SmsSenderInterface;
use app\infrastructure\services\LogCategory;
use app\infrastructure\services\YiiPsrLogger;
use Throwable;
use Yii;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

final class NotifySingleSubscriberJob implements JobInterface, RetryableJobInterface
{
    private const int TTR_SECONDS = 30;

    public function __construct(
        public string $phone,
        public string $message,
        public int $bookId,
    ) {
    }

    /** @codeCoverageIgnore Выполнение джобы: зависит от Yii DI и кэша */
    public function execute($queue): void
    {
        $cache = Yii::$app->cache;
        $idempotencyKey = sprintf('sms_handled:%d:%s', $this->bookId, md5($this->phone));

        if ($cache !== null && !$cache->add($idempotencyKey, 1, 3600 * 24)) {
            return;
        }

        $sender = Yii::$container->get(SmsSenderInterface::class);
        $logger = new YiiPsrLogger(LogCategory::SMS);

        try {
            $sender->send($this->phone, $this->message);

            $logger->info('SMS notification sent successfully', [
                'phone' => $this->phone,
                'book_id' => $this->bookId,
            ]);
        } catch (Throwable $exception) {
            $cache?->delete($idempotencyKey);

            $logger->error('SMS notification failed', [
                'phone' => $this->phone,
                'book_id' => $this->bookId,
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
    }

    public function getTtr(): int
    {
        return self::TTR_SECONDS;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < 3;
    }
}
