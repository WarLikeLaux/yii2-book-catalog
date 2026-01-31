<?php

declare(strict_types=1);

namespace tests\unit\presentation\auth\handlers;

use app\presentation\auth\dto\LoginViewModel;
use app\presentation\auth\forms\LoginForm;
use app\presentation\auth\handlers\AuthViewDataFactory;
use Codeception\Test\Unit;

final class AuthViewDataFactoryTest extends Unit
{
    public function testGetLoginViewModelReturnsViewModel(): void
    {
        $factory = new AuthViewDataFactory();

        $viewModel = $factory->getLoginViewModel();

        $this->assertInstanceOf(LoginViewModel::class, $viewModel);
        $this->assertInstanceOf(LoginForm::class, $viewModel->form);
        $this->assertSame('', $viewModel->form->username);
        $this->assertSame('', $viewModel->form->password);
        $this->assertTrue($viewModel->form->rememberMe);
    }
}
