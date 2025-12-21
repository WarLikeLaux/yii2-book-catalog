<?php

declare(strict_types=1);

namespace app\models\forms;

use Yii;
use yii\base\Model;
use yii\web\Request;

final class ReportFilterForm extends Model
{
    /** @var int|string|null */
    public $year = null;

    public function loadFromRequest(Request $request): bool
    {
        return $this->load($request->get());
    }

    public function rules(): array
    {
        return [
            ['year', 'integer', 'min' => 1900, 'max' => 2100],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'year' => Yii::t('app', 'Year'),
        ];
    }

    public function formName(): string
    {
        return '';
    }
}
