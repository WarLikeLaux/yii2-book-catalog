<?php

declare(strict_types=1);

namespace tests\unit\presentation\auth\handlers;

use app\application\common\config\ApiPageConfig;
use app\presentation\auth\dto\LoginViewModel;
use app\presentation\auth\forms\LoginForm;
use app\presentation\auth\handlers\AuthViewFactory;
use PHPUnit\Framework\TestCase;

final class AuthViewFactoryTest extends TestCase
{
    public function testGetLoginViewModelReturnsViewModel(): void
    {
        $factory = new AuthViewFactory($this->createApiPageConfig());

        $viewModel = $factory->getLoginViewModel();

        $this->assertInstanceOf(LoginViewModel::class, $viewModel);
        $this->assertInstanceOf(LoginForm::class, $viewModel->form);
        $this->assertSame('', $viewModel->form->username);
        $this->assertSame('', $viewModel->form->password);
        $this->assertTrue($viewModel->form->rememberMe);
    }

    public function testGetApiInfoViewModelReturnsViewModel(): void
    {
        $factory = new AuthViewFactory(new ApiPageConfig(8080, 8000));

        $viewModel = $factory->getApiInfoViewModel('example.test', false);

        $this->assertSame('http://example.test:8080', $viewModel->swaggerUrl);
        $this->assertSame('http://example.test:8000/api/v1', $viewModel->baseApiUrl);
    }

    public function testGetApiInfoViewModelReturnsHttpsUrls(): void
    {
        $factory = new AuthViewFactory(new ApiPageConfig(8080, 8000));

        $viewModel = $factory->getApiInfoViewModel('example.test', true);

        $this->assertSame('https://example.test:8080', $viewModel->swaggerUrl);
        $this->assertSame('https://example.test:8000/api/v1', $viewModel->baseApiUrl);
    }

    private function createApiPageConfig(): ApiPageConfig
    {
        return new ApiPageConfig(8081, 8000);
    }
}
