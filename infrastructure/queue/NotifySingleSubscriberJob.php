<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use app\application\ports\SmsSenderInterface;
use app\infrastructure\services\YiiPsrLogger;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\queue\Job;
use yii\queue\RetryableJobInterface;

final class NotifySingleSubscriberJob extends BaseObject implements Job, RetryableJobInterface
{
    private const int TTR_SECONDS = 30;

    public string $phone;

    public string $message;

    public int $bookId;

    public function execute($queue): void
    {
        $sender = Yii::$container->get(SmsSenderInterface::class);
        $logger = new YiiPsrLogger('sms');

        try {
            $sender->send($this->phone, $this->message);

            $logger->info('SMS notification sent successfully', [
                'phone' => $this->phone,
                'book_id' => $this->bookId,
            ]);
        } catch (Throwable $exception) {
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
