<?php

declare(strict_types=1);

namespace app\infrastructure\services\notifications;

use app\application\ports\NotificationInterface;
use Yii;
use yii\web\Application;
use yii\web\Session;

final readonly class FlashNotificationService implements NotificationInterface
{
    public function success(string $message): void
    {
        $this->getSession()?->setFlash('success', $message);
    }

    public function error(string $message): void
    {
        $this->getSession()?->setFlash('error', $message);
    }

    public function info(string $message): void
    {
        $this->getSession()?->setFlash('info', $message);
    }

    public function warning(string $message): void
    {
        $this->getSession()?->setFlash('warning', $message);
    }

    private function getSession(): ?Session
    {
        $app = Yii::$app;
        return $app instanceof Application ? $app->session : null;
    }
}
