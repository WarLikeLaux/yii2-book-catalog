<?php

declare(strict_types=1);

namespace app\presentation\reports\mappers;

use app\application\reports\queries\ReportCriteria;
use app\presentation\reports\forms\ReportFilterForm;

final readonly class ReportCriteriaMapper
{
    public function toCriteria(ReportFilterForm $form): ReportCriteria
    {
        return new ReportCriteria(
            year: $form->year !== null && $form->year !== '' ? (int)$form->year : null,
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    public function toForm(array $params): ReportFilterForm
    {
        $form = new ReportFilterForm();
        $form->load($params);
        return $form;
    }
}
