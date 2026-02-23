<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

use yii\base\BaseObject;
use yii\web\IdentityInterface;

final class User extends BaseObject implements IdentityInterface
{
    /** @var array<int|string, array<string, string>> */
    private static $_users = [
        '100' => [
            'id' => '100',
            'username' => 'admin',
            'password' => 'admin',
            'authKey' => 'test100key',
            'accessToken' => '100-token',
        ],
        '101' => [
            'id' => '101',
            'username' => 'demo',
            'password' => 'demo',
            'authKey' => 'test101key',
            'accessToken' => '101-token',
        ],
    ];
    public string $id;
    public string $username;
    public string $password;
    public string $authKey;
    public string $accessToken;

    public static function findIdentity($id)
    {
        return isset(self::$_users[$id]) ? new static(self::$_users[$id]) : null;
    }

    public static function findIdentityByAccessToken($token, $_type = null)
    {
        foreach (self::$_users as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }

    public static function findByUsername(string $username): ?static
    {
        foreach (self::$_users as $user) {
            if (strcasecmp($user['username'], $username) === 0) {
                return new static($user);
            }
        }

        return null;
    }

    /** @return string */
    public function getId()
    {
        return $this->id;
    }

    /** @return string */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /** @return bool */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return $this->password === $password;
    }
}
