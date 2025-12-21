<?php

declare(strict_types=1);

namespace app\controllers;

use app\application\reports\queries\ReportQueryService;
use app\application\UseCaseExecutor;
use app\models\forms\ReportFilterForm;
use Yii;
use yii\web\Controller;

final class ReportController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly ReportQueryService $reportQueryService,
        private readonly UseCaseExecutor $useCaseExecutor,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        $form = new ReportFilterForm();
        $form->loadFromRequest($this->request);
        if (!$form->validate()) {
            $data = $this->reportQueryService->getEmptyTopAuthorsReport();
            return $this->render('index', [
                'topAuthors' => $data->topAuthors,
                'year' => $data->year,
            ]);
        }

        $data = $this->useCaseExecutor->query(
            fn() => $this->reportQueryService->getTopAuthorsReport($form),
            $this->reportQueryService->getEmptyTopAuthorsReport($form->year),
            Yii::t('app', 'Error while generating report. Please contact administrator.')
        );

        return $this->render('index', [
            'topAuthors' => $data->topAuthors,
            'year' => $data->year,
        ]);
    }
}
