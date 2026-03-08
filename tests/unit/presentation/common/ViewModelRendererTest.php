<?php

declare(strict_types=1);

namespace tests\unit\presentation\common;

use app\presentation\common\ViewModelInterface;
use app\presentation\common\ViewModelRenderer;
use LogicException;
use PHPUnit\Framework\TestCase;
use yii\web\Controller;

final class ViewModelRendererTest extends TestCase
{
    private ViewModelRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new ViewModelRenderer();
    }

    public function testRenderThrowsExceptionWhenControllerNotSet(): void
    {
        $viewModel = $this->createStub(ViewModelInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Controller not set in ViewModelRenderer');

        $this->renderer->render('index', $viewModel);
    }

    public function testRenderThrowsExceptionWhenControllerIsNotBaseController(): void
    {
        $controller = $this->createStub(Controller::class);
        $viewModel = $this->createStub(ViewModelInterface::class);
        $this->renderer->setController($controller);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Controller must be instance of BaseController');

        $this->renderer->render('index', $viewModel);
    }

    public function testRenderPartialThrowsExceptionWhenControllerNotSet(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Controller not set in ViewModelRenderer');

        $this->renderer->renderPartial('_form');
    }
}
