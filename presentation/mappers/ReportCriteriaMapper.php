<?php

declare(strict_types=1);

namespace app\presentation\mappers;

use app\application\reports\queries\ReportCriteria;
use app\models\forms\ReportFilterForm;

final class ReportCriteriaMapper
{
    public function toCriteria(ReportFilterForm $form): ReportCriteria
    {
        return new ReportCriteria(
            year: $form->year ? (int)$form->year : null
        );
    }

    public function toForm(array $params): ReportFilterForm
    {
        $form = new ReportFilterForm();
        $form->loadFromRequest(\Yii::$app->request);
        return $form;
    }
}
