<?php

declare(strict_types=1);

namespace app\presentation\reports\handlers;

use app\application\reports\queries\ReportDto;
use app\application\reports\queries\ReportQueryService;
use app\presentation\common\services\WebUseCaseRunner;
use app\presentation\reports\mappers\ReportCriteriaMapper;
use Yii;
use yii\web\Request;

final readonly class ReportHandler
{
    public function __construct(
        private ReportCriteriaMapper $reportCriteriaMapper,
        private ReportQueryService $reportQueryService,
        private WebUseCaseRunner $useCaseRunner
    ) {
    }

    /**
     * @codeCoverageIgnore Использует Yii::t() и WebUseCaseRunner с flash-сообщениями
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
        $data = $this->useCaseRunner->query(
            fn(): ReportDto => $this->reportQueryService->getTopAuthorsReport($criteria),
            $this->reportQueryService->getEmptyTopAuthorsReport($form->year !== null && $form->year !== '' ? (int)$form->year : null),
            Yii::t('app', 'Error while generating report. Please contact administrator.')
        );

        return [
            'topAuthors' => $data->topAuthors,
            'year' => $data->year,
        ];
    }
}
