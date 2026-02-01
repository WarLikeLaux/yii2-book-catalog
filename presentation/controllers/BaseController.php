<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\common\ViewModelRenderer;
use LogicException;
use Override;
use yii\web\Controller;

abstract class BaseController extends Controller
{
    public function __construct(
        $id,
        $module,
        protected ViewModelRenderer $renderer,
        $config = [],
    ) {
        $this->renderer->setController($this);
        parent::__construct($id, $module, $config);
    }

    private function renderInternal(string $view, array $params): string
    {
        return parent::render($view, $params);
    }

    #[Override]
    public function render($_view, $_params = []): string
    {
        if ($_view === 'error') {
            return parent::render($_view, $_params);
        }

        throw new LogicException(
            'Use $this->renderer->render() for rendering views. Example: $this->renderer->render("view", $viewModel)',
        );
    }
}
