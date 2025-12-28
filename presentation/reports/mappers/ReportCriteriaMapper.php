<?php

declare(strict_types=1);

namespace app\presentation\reports\mappers;

use app\application\reports\queries\ReportCriteria;
use app\presentation\reports\forms\ReportFilterForm;
use yii\web\Request;

final class ReportCriteriaMapper
{
    public function toCriteria(ReportFilterForm $form): ReportCriteria
    {
        return new ReportCriteria(
            year: $form->year !== null && $form->year !== '' ? (int)$form->year : null
        );
    }

    public function toForm(Request $request): ReportFilterForm
    {
        $form = new ReportFilterForm();
        $form->loadFromRequest($request);
        return $form;
    }
}
