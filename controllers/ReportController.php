<?php

declare(strict_types=1);

namespace app\controllers;

use app\services\ReportService;
use yii\web\Controller;

final class ReportController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly ReportService $reportService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        $year = (int)($this->request->get('year') ?: date('Y'));

        $topAuthors = $this->reportService->getTopAuthorsByYear($year);

        return $this->render('index', [
            'topAuthors' => $topAuthors,
            'year' => $year,
        ]);
    }
}
