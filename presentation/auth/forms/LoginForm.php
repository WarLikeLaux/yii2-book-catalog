<?php

declare(strict_types=1);

namespace app\presentation\auth\forms;

use app\infrastructure\persistence\User;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Yii;
use yii\base\Model;

final class LoginForm extends Model
{
    public string $username = '';

    public string $password = '';

    public bool $rememberMe = true;

    private User|false|null $_user = false;

    #[\Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    #[\Override]
    #[CodeCoverageIgnore]
    public function attributeLabels(): array
    {
        return [
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
            'rememberMe' => Yii::t('app', 'Remember Me'),
        ];
    }

    public function validatePassword(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();

        if ($user instanceof User && $user->validatePassword($this->password)) {
            return;
        }

        $this->addError($attribute, Yii::t('app', 'Incorrect username or password.'));
    }

    public function getUser(): ?User
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}
