<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\common\UseCaseExecutor;
use app\application\reports\queries\ReportQueryService;
use app\presentation\mappers\ReportCriteriaMapper;
use Yii;
use yii\web\Request;

final class ReportPresentationService
{
    public function __construct(
        private readonly ReportCriteriaMapper $reportCriteriaMapper,
        private readonly ReportQueryService $reportQueryService,
        private readonly UseCaseExecutor $useCaseExecutor
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareIndexViewData(Request $request): array
    {
        $form = $this->reportCriteriaMapper->toForm($request);

        if (!$form->validate()) {
            $data = $this->reportQueryService->getEmptyTopAuthorsReport();
            return [
                'topAuthors' => $data->topAuthors,
                'year' => $data->year,
            ];
        }

        $criteria = $this->reportCriteriaMapper->toCriteria($form);
        /** @var \app\application\reports\queries\ReportDto $data */
        $data = $this->useCaseExecutor->query(
            fn() => $this->reportQueryService->getTopAuthorsReport($criteria),
            $this->reportQueryService->getEmptyTopAuthorsReport($form->year ? (int)$form->year : null),
            Yii::t('app', 'Error while generating report. Please contact administrator.')
        );

        return [
            'topAuthors' => $data->topAuthors,
            'year' => $data->year,
        ];
    }
}
