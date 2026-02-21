<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\application\ports\RequestIdProviderInterface;
use app\presentation\common\ViewModelRenderer;
use app\presentation\reports\handlers\ReportViewFactory;

final class ReportController extends BaseController
{
    public function __construct(
        $id,
        $module,
        private readonly ReportViewFactory $reportViewFactory,
        ViewModelRenderer $renderer,
        RequestIdProviderInterface $requestIdProvider,
        $config = [],
    ) {
        parent::__construct($id, $module, $renderer, $requestIdProvider, $config);
    }

    public function actionIndex(): string
    {
        /** @var array<string, mixed> $params */
        $params = $this->request->get();
        $viewModel = $this->reportViewFactory->prepareIndexViewModel($params);
        return $this->renderer->render('index', $viewModel);
    }
}
