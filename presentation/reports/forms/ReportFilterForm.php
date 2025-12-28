<?php

declare(strict_types=1);

namespace app\presentation\reports\forms;

use Yii;
use yii\base\Model;
use yii\web\Request;

final class ReportFilterForm extends Model
{
    /** @var int|string|null */
    public $year;

    /**
     * @codeCoverageIgnore Делегирует загрузку данных из запроса в стандартный метод Yii2
     */
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

    /**
     * @codeCoverageIgnore Убирает префикс формы для работы с плоскими параметрами запроса
     */
    #[\Override]
    public function formName(): string
    {
        return '';
    }
}
