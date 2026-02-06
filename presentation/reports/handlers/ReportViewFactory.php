<?php

declare(strict_types=1);

namespace app\presentation\reports\handlers;

use app\application\ports\ReportQueryServiceInterface;
use app\application\reports\queries\ReportDto;
use app\presentation\common\services\WebOperationRunner;
use app\presentation\reports\dto\ReportViewModel;
use app\presentation\reports\mappers\ReportCriteriaMapper;
use Yii;

final readonly class ReportViewFactory
{
    public function __construct(
        private ReportCriteriaMapper $reportCriteriaMapper,
        private ReportQueryServiceInterface $reportQueryService,
        private WebOperationRunner $operationRunner,
    ) {
    }

    /**
     * @param array<string, mixed> $queryParams
     * @codeCoverageIgnore Использует Yii::t() и WebOperationRunner с flash-сообщениями
     */
    public function prepareIndexViewModel(array $queryParams): ReportViewModel
    {
        $form = $this->reportCriteriaMapper->toForm($queryParams);

        if (!$form->validate()) {
            $data = $this->reportQueryService->getEmptyTopAuthorsReport();
            return new ReportViewModel(
                $data->topAuthors,
                $data->year,
            );
        }

        $criteria = $this->reportCriteriaMapper->toCriteria($form);
        /** @var ReportDto $data */
        $data = $this->operationRunner->query(
            fn(): ReportDto => $this->reportQueryService->getTopAuthorsReport($criteria),
            $this->reportQueryService->getEmptyTopAuthorsReport($form->year !== null && $form->year !== '' ? (int)$form->year : null),
            Yii::t('app', 'report.error.generate_failed'),
        );

        return new ReportViewModel(
            $data->topAuthors,
            $data->year,
        );
    }
}
