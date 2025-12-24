<?php

declare(strict_types=1);

namespace app\presentation\forms;

use yii\base\Model;

final class BookSearchForm extends Model
{
    public string $globalSearch = '';

    public function rules(): array
    {
        return [
            [['globalSearch'], 'string', 'min' => 2],
            [['globalSearch'], 'trim'],
        ];
    }

    public function formName(): string
    {
        return '';
    }
}
