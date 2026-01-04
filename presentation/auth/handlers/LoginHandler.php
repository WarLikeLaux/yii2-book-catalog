<?php

declare(strict_types=1);

namespace app\presentation\auth\handlers;

use app\presentation\auth\forms\LoginForm;
use Yii;

final class LoginHandler
{
    /**
     * @return array<string, LoginForm>
     */
    public function prepareLoginViewData(): array
    {
        $form = new LoginForm();

        return [
            'model' => $form,
        ];
    }

    /**
     * @codeCoverageIgnore Зависит от Yii::$app->user->login()
     * @param array<string, mixed> $postData
     * @return array{success: bool, viewData: array<string, mixed>}
     */
    public function processLoginRequest(array $postData): array
    {
        $viewData = $this->prepareLoginViewData();
        $form = $viewData['model'];

        if (!$form->load($postData)) {
            return [
                'success' => false,
                'viewData' => $viewData,
            ];
        }

        if (!$form->validate()) {
            $form->password = '';
            return [
                'success' => false,
                'viewData' => ['model' => $form],
            ];
        }

        $user = $form->getUser();
        if ($user === null || !$user->validatePassword($form->password)) {
            $form->addError('password', Yii::t('app', 'auth.error.invalid_credentials'));
            $form->password = '';
            return [
                'success' => false,
                'viewData' => ['model' => $form],
            ];
        }

        $duration = $form->rememberMe ? 3600 * 24 * 30 : 0;
        Yii::$app->user->login($user, $duration);

        return [
            'success' => true,
            'viewData' => $viewData,
        ];
    }
}
