<?php

declare(strict_types=1);

namespace app\presentation\auth\handlers;

use app\application\common\config\ApiPageConfig;
use app\presentation\auth\dto\ApiInfoViewModel;
use app\presentation\auth\dto\LoginViewModel;
use app\presentation\auth\forms\LoginForm;

final readonly class AuthViewFactory
{
    public function __construct(
        private ApiPageConfig $apiPageConfig,
    ) {
    }

    public function getLoginViewModel(LoginForm|null $form = null): LoginViewModel
    {
        return new LoginViewModel(
            $form ?? new LoginForm(),
        );
    }

    public function getApiInfoViewModel(string $host, bool $isSecure): ApiInfoViewModel
    {
        $scheme = $isSecure ? 'https://' : 'http://';

        return new ApiInfoViewModel(
            "{$scheme}{$host}:{$this->apiPageConfig->swaggerPort}",
            "{$scheme}{$host}:{$this->apiPageConfig->appPort}/api/v1",
        );
    }
}
