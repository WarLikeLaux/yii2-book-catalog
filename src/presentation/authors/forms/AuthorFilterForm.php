<?php

declare(strict_types=1);

namespace app\presentation\authors\forms;

use Override;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use yii\base\Model;

final class AuthorFilterForm extends Model
{
    public string $fio = '';

    /** @var int|string|null */
    public $id;

    #[Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            ['fio', 'trim'],
            ['fio', 'string'],
            ['id', 'integer'],
        ];
    }

    #[Override]
    #[CodeCoverageIgnore]
    public function formName(): string
    {
        return '';
    }
}
