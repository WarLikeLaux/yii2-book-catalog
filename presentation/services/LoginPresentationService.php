<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\presentation\forms\LoginForm;
use Yii;
use yii\web\Request;
use yii\web\Response;

final class LoginPresentationService
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
     * @return array{success: bool, viewData: array<string, mixed>}
     */
    public function processLoginRequest(Request $request, Response $response): array
    {
        $viewData = $this->prepareLoginViewData();
        $form = $viewData['model'];

        if (!$form->load((array)$request->post())) {
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
        if (!$user || !$user->validatePassword($form->password)) {
            $form->addError('password', Yii::t('app', 'Incorrect username or password.'));
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
