<?php

declare(strict_types=1);

namespace app\presentation\auth\dto;

use app\presentation\auth\forms\LoginForm;

final readonly class LoginViewModel
{
    public function __construct(
        public LoginForm $form,
    ) {
    }
}
