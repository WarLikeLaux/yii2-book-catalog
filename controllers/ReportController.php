<?php

declare(strict_types=1);

namespace app\controllers;

use app\presentation\services\ReportPresentationService;
use yii\web\Controller;

final class ReportController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly ReportPresentationService $reportPresentationService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        $viewData = $this->reportPresentationService->prepareIndexViewData($this->request);
        return $this->render('index', $viewData);
    }
}
