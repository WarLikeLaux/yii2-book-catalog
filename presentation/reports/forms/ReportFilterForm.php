<?php

declare(strict_types=1);

namespace app\presentation\reports\forms;

use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Yii;
use yii\base\Model;
use yii\web\Request;

final class ReportFilterForm extends Model
{
    /** @var int|string|null */
    public $year;

    #[CodeCoverageIgnore]
    public function loadFromRequest(Request $request): bool
    {
        return $this->load((array)$request->get());
    }

    #[\Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            ['year', 'integer', 'min' => 1900, 'max' => 2100],
        ];
    }

    #[\Override]
    #[CodeCoverageIgnore]
    public function attributeLabels(): array
    {
        return [
            'year' => Yii::t('app', 'ui.year'),
        ];
    }

    #[\Override]
    #[CodeCoverageIgnore]
    public function formName(): string
    {
        return '';
    }
}
