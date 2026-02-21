<?php

declare(strict_types=1);

namespace app\presentation\authors\forms;

use Override;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Yii;
use yii\base\Model;
use yii\web\Request;

final class AuthorForm extends Model
{
    /** @var int|string|null */
    public $id;

    /** @var string|int|null */
    public $fio = '';

    #[Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            [['fio'], 'required'],
            [['fio'], 'string', 'max' => 255],
            [['fio'], 'trim'],
        ];
    }

    #[Override]
    #[CodeCoverageIgnore]
    public function attributeLabels(): array
    {
        return [
            'fio' => Yii::t('app', 'ui.fio'),
        ];
    }

    #[CodeCoverageIgnore]
    public function loadFromRequest(Request $request): bool
    {
        return $this->load((array)$request->post());
    }
}
