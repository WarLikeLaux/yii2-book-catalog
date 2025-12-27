<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use app\application\subscriptions\queries\SubscriptionQueryService;
use app\infrastructure\services\YiiPsrLogger;
use Yii;
use yii\base\BaseObject;
use yii\queue\Job;
use yii\queue\RetryableJobInterface;

final class NotifySubscribersJob extends BaseObject implements Job, RetryableJobInterface
{
    private const int TTR_SECONDS = 300;

    public int $bookId;

    public string $title;

    /**
     * @codeCoverageIgnore
     */
    public function execute($queue): void
    {
        $logger = new YiiPsrLogger('sms');
        /** @var \yii\queue\Queue $jobQueue */
        $jobQueue = Yii::$app->get('queue');
        $queryService = Yii::$container->get(SubscriptionQueryService::class);

        $message = Yii::t('app', 'New book released: {title}', ['title' => $this->title]);
        $totalDispatched = 0;

        foreach ($queryService->getSubscriberPhonesForBook($this->bookId) as $phone) {
            $jobQueue->push(new NotifySingleSubscriberJob([
                'phone' => $phone,
                'message' => $message,
                'bookId' => $this->bookId,
            ]));
            $totalDispatched++;
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
