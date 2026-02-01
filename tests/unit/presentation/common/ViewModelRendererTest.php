<?php

declare(strict_types=1);

namespace tests\unit\presentation\common;

use app\presentation\common\ViewModelInterface;
use app\presentation\common\ViewModelRenderer;
use Codeception\Test\Unit;
use LogicException;
use yii\web\Controller;

final class ViewModelRendererTest extends Unit
{
    private ViewModelRenderer $renderer;

    protected function _before(): void
    {
        $this->renderer = new ViewModelRenderer();
    }

    public function testRenderThrowsExceptionWhenControllerNotSet(): void
    {
        $viewModel = $this->createMock(ViewModelInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Controller not set in ViewModelRenderer');

        $this->renderer->render('index', $viewModel);
    }

    public function testRenderThrowsExceptionWhenControllerIsNotBaseController(): void
    {
        $controller = $this->createMock(Controller::class);
        $viewModel = $this->createMock(ViewModelInterface::class);
        $this->renderer->setController($controller);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Controller must be instance of BaseController');

        $this->renderer->render('index', $viewModel);
    }
}
