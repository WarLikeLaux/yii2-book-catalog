<?php

declare(strict_types=1);

namespace app\presentation\components;

use Override;
use yii\base\Model;
use yii\bootstrap5\ActiveForm as BaseActiveForm;

final class ActiveForm extends BaseActiveForm
{
    /**
     * @param Model|array<Model> $models
     * @param array $options
     * @phpstan-param array<array-key, mixed> $options
     */
    #[Override]
    public function errorSummary($models, $options = []): string
    {
        return ErrorSummaryWidget::widget([
            'models' => $models,
            'options' => $options,
        ]);
    }
}
