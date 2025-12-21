<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\models\forms\LoginForm;
use yii\web\Request;
use yii\web\Response;

final class LoginPresentationService
{
    public function prepareLoginViewData(): array
    {
        $form = new LoginForm();

        return [
            'model' => $form,
        ];
    }

    public function processLoginRequest(Request $request, Response $response): array
    {
        $viewData = $this->prepareLoginViewData();
        $form = $viewData['model'];

        if (!$form->load($request->post())) {
            return [
                'success' => false,
                'viewData' => $viewData,
            ];
        }

        if (!$form->login()) {
            $form->password = '';
            return [
                'success' => false,
                'viewData' => ['model' => $form],
            ];
        }

        return [
            'success' => true,
            'viewData' => $viewData,
        ];
    }
}
