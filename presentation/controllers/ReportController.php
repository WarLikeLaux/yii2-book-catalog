<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\common\ViewModelRenderer;
use app\presentation\reports\handlers\ReportHandler;

final class ReportController extends BaseController
{
    public function __construct(
        $id,
        $module,
        private readonly ReportHandler $reportHandler,
        ViewModelRenderer $renderer,
        $config = [],
    ) {
        parent::__construct($id, $module, $renderer, $config);
    }

    public function actionIndex(): string
    {
        /** @var array<string, mixed> $params */
        $params = $this->request->get();
        $viewModel = $this->reportHandler->prepareIndexViewModel($params);
        return $this->renderer->render('index', $viewModel);
    }
}
