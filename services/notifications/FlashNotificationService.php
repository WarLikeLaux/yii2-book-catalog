<?php

declare(strict_types=1);

namespace app\services\notifications;

use app\application\ports\NotificationInterface;
use Yii;

final class FlashNotificationService implements NotificationInterface
{
    public function success(string $message): void
    {
        Yii::$app->session->setFlash('success', $message);
    }

    public function error(string $message): void
    {
        Yii::$app->session->setFlash('error', $message);
    }

    public function info(string $message): void
    {
        Yii::$app->session->setFlash('info', $message);
    }

    public function warning(string $message): void
    {
        Yii::$app->session->setFlash('warning', $message);
    }
}
