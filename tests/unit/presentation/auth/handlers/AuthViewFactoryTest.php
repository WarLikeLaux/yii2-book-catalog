<?php

declare(strict_types=1);

namespace tests\unit\presentation\auth\handlers;

use app\presentation\auth\dto\LoginViewModel;
use app\presentation\auth\forms\LoginForm;
use app\presentation\auth\handlers\AuthViewFactory;
use Codeception\Test\Unit;

final class AuthViewFactoryTest extends Unit
{
    public function testGetLoginViewModelReturnsViewModel(): void
    {
        $factory = new AuthViewFactory();

        $viewModel = $factory->getLoginViewModel();

        $this->assertInstanceOf(LoginViewModel::class, $viewModel);
        $this->assertInstanceOf(LoginForm::class, $viewModel->form);
        $this->assertSame('', $viewModel->form->username);
        $this->assertSame('', $viewModel->form->password);
        $this->assertTrue($viewModel->form->rememberMe);
    }

    public function testGetApiInfoViewModelReturnsViewModel(): void
    {
        $factory = new AuthViewFactory();

        $viewModel = $factory->getApiInfoViewModel(8080, 8000, 'example.test');

        $this->assertSame(8080, $viewModel->swaggerPort);
        $this->assertSame(8000, $viewModel->appPort);
        $this->assertSame('example.test', $viewModel->host);
    }
}
