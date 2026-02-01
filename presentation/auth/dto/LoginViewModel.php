<?php

declare(strict_types=1);

namespace app\presentation\auth\dto;

use app\presentation\auth\forms\LoginForm;
use app\presentation\common\ViewModelInterface;

final readonly class LoginViewModel implements ViewModelInterface
{
    public function __construct(
        public LoginForm $form,
    ) {
    }
}
