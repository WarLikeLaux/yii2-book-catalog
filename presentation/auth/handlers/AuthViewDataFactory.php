<?php

declare(strict_types=1);

namespace app\presentation\auth\handlers;

use app\presentation\auth\dto\ApiInfoViewModel;
use app\presentation\auth\dto\LoginViewModel;
use app\presentation\auth\forms\LoginForm;

final readonly class AuthViewDataFactory
{
    public function getLoginViewModel(LoginForm|null $form = null): LoginViewModel
    {
        return new LoginViewModel(
            $form ?? new LoginForm(),
        );
    }

    public function getApiInfoViewModel(int $swaggerPort, int $appPort, string $host): ApiInfoViewModel
    {
        return new ApiInfoViewModel($swaggerPort, $appPort, $host);
    }
}
