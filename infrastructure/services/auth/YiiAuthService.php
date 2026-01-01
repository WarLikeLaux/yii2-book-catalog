<?php

declare(strict_types=1);

namespace app\infrastructure\services\auth;

use app\application\ports\AuthServiceInterface;
use Yii;

final class YiiAuthService implements AuthServiceInterface
{
    public function isGuest(): bool
    {
        return Yii::$app->user->isGuest;
    }

    public function logout(): void
    {
        Yii::$app->user->logout();
    }
}
