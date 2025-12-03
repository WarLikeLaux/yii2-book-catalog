<?php

declare(strict_types=1);

namespace app\jobs;

use app\models\Subscription;
use Psr\Log\LoggerInterface;
use Yii;
use yii\base\BaseObject;
use yii\queue\db\Queue;
use yii\queue\Job;
use yii\queue\RetryableJobInterface;

final class NotifySubscribersJob extends BaseObject implements Job, RetryableJobInterface
{
    private const int TTR_SECONDS = 300;
    private const int BATCH_SIZE = 100;

    public int $bookId;
    public string $title;

    public function execute($queue): void
    {
        $logger = Yii::$container->get(LoggerInterface::class);
        $jobQueue = Yii::$container->get(Queue::class);

        $query = Subscription::find()
            ->alias('s')
            ->select('s.phone')
            ->distinct()
            ->innerJoin('book_authors ba', 'ba.author_id = s.author_id')
            ->andWhere(['ba.book_id' => $this->bookId]);

        $message = "Вышла новая книга: {$this->title}";
        $totalDispatched = 0;

        foreach ($query->batch(self::BATCH_SIZE) as $batch) {
            foreach ($batch as $row) {
                $phone = $row['phone'];

                $jobQueue->push(new NotifySingleSubscriberJob([
                    'phone' => $phone,
                    'message' => $message,
                    'bookId' => $this->bookId,
                ]));

                $totalDispatched++;
            }
        }

        $logger->info('SMS notification jobs dispatched', [
            'book_id' => $this->bookId,
            'book_title' => $this->title,
            'total_jobs' => $totalDispatched,
        ]);
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
