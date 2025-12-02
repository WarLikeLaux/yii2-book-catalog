<?php

declare(strict_types=1);

namespace app\jobs;

use app\interfaces\SmsSenderInterface;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\db\Query;
use yii\queue\Job;
use yii\queue\RetryableJobInterface;

final class NotifySubscribersJob extends BaseObject implements Job, RetryableJobInterface
{
    private const MAX_FAILURES = 3;
    private const TTR_SECONDS = 60;

    public int $bookId;
    public string $title;

    public function execute($queue): void
    {
        $sender = Yii::$container->get(SmsSenderInterface::class);

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
                Yii::error("SMS fail to {$phone}: " . $exception->getMessage(), 'sms');
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
