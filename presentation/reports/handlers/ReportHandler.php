<?php

declare(strict_types=1);

namespace app\presentation\reports\handlers;

use app\application\ports\ReportQueryServiceInterface;
use app\application\reports\queries\ReportDto;
use app\presentation\common\services\WebUseCaseRunner;
use app\presentation\reports\mappers\ReportCriteriaMapper;
use Yii;

final readonly class ReportHandler
{
    public function __construct(
        private ReportCriteriaMapper $reportCriteriaMapper,
        private ReportQueryServiceInterface $reportQueryService,
        private WebUseCaseRunner $useCaseRunner,
    ) {
    }

    /**
     * @param array<string, mixed> $queryParams
     * @codeCoverageIgnore Использует Yii::t() и WebUseCaseRunner с flash-сообщениями
     * @return array<string, mixed>
     */
    public function prepareIndexViewData(array $queryParams): array
    {
        $form = $this->reportCriteriaMapper->toForm($queryParams);

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
            Yii::t('app', 'report.error.generate_failed'),
        );

        return [
            'topAuthors' => $data->topAuthors,
            'year' => $data->year,
        ];
    }
}
