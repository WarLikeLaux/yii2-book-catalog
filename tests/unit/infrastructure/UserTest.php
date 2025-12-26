<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\persistence\User;
use Codeception\Test\Unit;

final class UserTest extends Unit
{
    public function testFindIdentity(): void
    {
        $user = User::findIdentity('100');
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('100', $user->getId());
    }

    public function testFindIdentityReturnsNull(): void
    {
        $this->assertNull(User::findIdentity('999'));
    }

    public function testFindByUsername(): void
    {
        $user = User::findByUsername('admin');
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('admin', $user->username);
    }

    public function testFindByUsernameReturnsNull(): void
    {
        $this->assertNull(User::findByUsername('nonexistent'));
    }

    public function testFindIdentityByAccessToken(): void
    {
        $user = User::findIdentityByAccessToken('100-token');
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('100', $user->getId());
    }

    public function testFindIdentityByAccessTokenReturnsNull(): void
    {
        $this->assertNull(User::findIdentityByAccessToken('invalid-token'));
    }

    public function testGetAuthKey(): void
    {
        $user = User::findIdentity('100');
        $this->assertSame('test100key', $user->getAuthKey());
    }

    public function testValidateAuthKey(): void
    {
        $user = User::findIdentity('100');
        $this->assertTrue($user->validateAuthKey('test100key'));
        $this->assertFalse($user->validateAuthKey('wrong-key'));
    }

    public function testValidatePassword(): void
    {
        $user = User::findByUsername('admin');
        $this->assertTrue($user->validatePassword('admin'));
        $this->assertFalse($user->validatePassword('wrong'));
    }
}
