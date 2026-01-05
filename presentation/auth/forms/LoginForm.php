<?php

declare(strict_types=1);

namespace app\presentation\auth\forms;

use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Yii;
use yii\base\Model;

final class LoginForm extends Model
{
    public string $username = '';
    public string $password = '';
    public bool $rememberMe = true;

    #[\Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
        ];
    }

    #[\Override]
    #[CodeCoverageIgnore]
    public function attributeLabels(): array
    {
        return [
            'username' => Yii::t('app', 'ui.username'),
            'password' => Yii::t('app', 'ui.password'),
            'rememberMe' => Yii::t('app', 'ui.remember_me'),
        ];
    }
}
