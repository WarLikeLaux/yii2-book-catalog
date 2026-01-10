<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\ports\AuthServiceInterface;
use app\infrastructure\persistence\User;
use Yii;
use yii\web\Application;

final class YiiAuthAdapter implements AuthServiceInterface
{
    private const int REMEMBER_ME_DURATION = 3600 * 24 * 30;

    public function isGuest(): bool
    {
        $app = Yii::$app;

        if (!$app instanceof Application) {
            return true;
        }

        return $app->user->isGuest;
    }

    public function login(string $username, string $password, bool $rememberMe): bool
    {
        $user = User::findByUsername($username);

        if ($user === null || !$user->validatePassword($password)) {
            return false;
        }

        $duration = $rememberMe ? self::REMEMBER_ME_DURATION : 0;

        $app = Yii::$app;

        if (!$app instanceof Application) {
            return false;
        }

        return $app->user->login($user, $duration);
    }

    public function logout(): void
    {
        $app = Yii::$app;

        if (!($app instanceof Application)) {
            return;
        }

        $app->user->logout();
    }
}
