<?php

declare(strict_types=1);

namespace app\jobs;

use app\interfaces\SmsSenderInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\db\Query;
use yii\queue\Job;
use yii\queue\RetryableJobInterface;

final class NotifySubscribersJob extends BaseObject implements Job, RetryableJobInterface
{
    private const int MAX_FAILURES = 3;
    private const int TTR_SECONDS = 60;

    public int $bookId;
    public string $title;

    public function execute($queue): void
    {
        $sender = Yii::$container->get(SmsSenderInterface::class);
        $logger = Yii::$container->get(LoggerInterface::class);

        $phones = (new Query())
            ->select('s.phone')
            ->distinct()
            ->from(['s' => 'subscriptions'])
            ->innerJoin(['ba' => 'book_authors'], 'ba.author_id = s.author_id')
            ->where(['ba.book_id' => $this->bookId])
            ->column();

        $failures = 0;
        foreach ($phones as $phone) {
            try {
                $sender->send($phone, "Вышла новая книга: {$this->title}");
                continue;
            } catch (Throwable $exception) {
                $logger->error('SMS notification failed', [
                    'phone' => $phone,
                    'book_id' => $this->bookId,
                    'book_title' => $this->title,
                    'error' => $exception->getMessage(),
                    'exception_class' => $exception::class,
                ]);
                $failures++;
            }

            if ($failures >= self::MAX_FAILURES) {
                throw $exception;
            }
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
