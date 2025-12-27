<?php

declare(strict_types=1);

namespace app\presentation\forms;

use Yii;
use yii\base\Model;
use yii\web\Request;

final class ReportFilterForm extends Model
{
    /** @var int|string|null */
    public $year;

    /** @codeCoverageIgnore Обёртка над Yii Model::load() */
    public function loadFromRequest(Request $request): bool
    {
        return $this->load((array)$request->get());
    }

    #[\Override]
    public function rules(): array
    {
        return [
            ['year', 'integer', 'min' => 1900, 'max' => 2100],
        ];
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            'year' => Yii::t('app', 'Year'),
        ];
    }

    /** @codeCoverageIgnore Переопределение Yii formName() */
    #[\Override]
    public function formName(): string
    {
        return '';
    }
}
