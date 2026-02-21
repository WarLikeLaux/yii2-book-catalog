<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\common\exceptions\OperationFailedException;
use app\application\ports\AuthServiceInterface;
use app\domain\exceptions\DomainErrorCode;
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

    public function login(string $username, string $password, bool $rememberMe): void
    {
        $user = User::findByUsername($username);

        if (!$user instanceof User || !$user->validatePassword($password)) {
            throw new OperationFailedException(DomainErrorCode::AuthInvalidCredentials->value, 'password');
        }

        $duration = $rememberMe ? self::REMEMBER_ME_DURATION : 0;

        $app = Yii::$app;

        if (!$app instanceof Application) {
            throw new OperationFailedException(DomainErrorCode::AuthInvalidCredentials->value, 'password');
        }

        // @codeCoverageIgnoreStart
        if (!$app->user->login($user, $duration)) {
            throw new OperationFailedException(DomainErrorCode::AuthInvalidCredentials->value, 'password');
        }
        // @codeCoverageIgnoreEnd
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
