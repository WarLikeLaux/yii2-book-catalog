<?php

declare(strict_types=1);

namespace app\presentation\common;

use app\presentation\controllers\BaseController;
use LogicException;
use ReflectionMethod;
use yii\web\Controller;

final class ViewModelRenderer
{
    private ?Controller $controller = null;

    public function setController(Controller $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(
        string $view,
        ViewModelInterface $viewModel,
        array $context = [],
    ): string {
        if (!$this->controller instanceof Controller) {
            throw new LogicException('Controller not set in ViewModelRenderer');
        }

        $params = array_merge(['viewModel' => $viewModel], $context);

        return $this->callRenderWithParams($view, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function callRenderWithParams(string $view, array $params): string
    {
        if (!$this->controller instanceof BaseController) {
            throw new LogicException('Controller must be instance of BaseController');
        }

        $reflection = new ReflectionMethod($this->controller, 'renderInternal');

        /** @var string $result */
        $result = $reflection->invoke($this->controller, $view, $params);

        return $result;
    }
}
