<?php

declare(strict_types=1);

namespace app\infrastructure\queue;

use app\application\ports\TranslatorInterface;
use app\application\subscriptions\queries\SubscriptionQueryService;
use app\infrastructure\services\YiiPsrLogger;
use Yii;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

/**
 * Джоб для уведомления подписчиков о новой книге.
 *
 * ВАЖНО: Мы не используем Dependency Injection в конструкторе, так как объект сериализуется
 * при попадании в очередь. Зависимости извлекаются в методе execute().
 */
final readonly class NotifySubscribersJob implements JobInterface, RetryableJobInterface
{
    private const int TTR_SECONDS = 300;

    public function __construct(
        public int $bookId,
        public string $title,
    ) {
    }

    /** @codeCoverageIgnore Fan-out джоба: зависит от Yii-очереди и внешних сервисов */
    public function execute($queue): void
    {
        $logger = new YiiPsrLogger('sms');
        /** @var \yii\queue\Queue $jobQueue */
        $jobQueue = Yii::$app->get('queue');

        /** @var SubscriptionQueryService $queryService */
        $queryService = Yii::$container->get(SubscriptionQueryService::class);
        /** @var TranslatorInterface $translator */
        $translator = Yii::$container->get(TranslatorInterface::class);

        $message = $translator->translate('app', 'New book released: {title}', ['title' => $this->title]);
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
