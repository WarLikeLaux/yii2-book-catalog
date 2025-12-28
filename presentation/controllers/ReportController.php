<?php

declare(strict_types=1);

namespace app\presentation\controllers;

use app\presentation\reports\handlers\ReportHandler;
use yii\web\Controller;

final class ReportController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly ReportHandler $reportHandler,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        $viewData = $this->reportHandler->prepareIndexViewData($this->request);
        return $this->render('index', $viewData);
    }
}
