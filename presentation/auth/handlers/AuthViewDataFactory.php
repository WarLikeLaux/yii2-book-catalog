<?php

declare(strict_types=1);

namespace app\presentation\auth\handlers;

use app\presentation\auth\forms\LoginForm;

final readonly class AuthViewDataFactory
{
    public function createLoginForm(): LoginForm
    {
        return new LoginForm();
    }
}
