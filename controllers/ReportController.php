<?php

declare(strict_types=1);

namespace app\controllers;

use app\application\reports\queries\ReportQueryService;
use app\presentation\mappers\ReportCriteriaMapper;
use app\presentation\UseCaseExecutor;
use Yii;
use yii\web\Controller;

final class ReportController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly ReportQueryService $reportQueryService,
        private readonly ReportCriteriaMapper $reportCriteriaMapper,
        private readonly UseCaseExecutor $useCaseExecutor,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): string
    {
        $form = $this->reportCriteriaMapper->toForm($this->request->get());
        if (!$form->validate()) {
            $data = $this->reportQueryService->getEmptyTopAuthorsReport();
            return $this->render('index', [
                'topAuthors' => $data->topAuthors,
                'year' => $data->year,
            ]);
        }

        $criteria = $this->reportCriteriaMapper->toCriteria($form);
        $data = $this->useCaseExecutor->query(
            fn() => $this->reportQueryService->getTopAuthorsReport($criteria),
            $this->reportQueryService->getEmptyTopAuthorsReport($form->year),
            Yii::t('app', 'Error while generating report. Please contact administrator.')
        );

        return $this->render('index', [
            'topAuthors' => $data->topAuthors,
            'year' => $data->year,
        ]);
    }
}
