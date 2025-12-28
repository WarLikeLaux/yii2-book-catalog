<?php

declare(strict_types=1);

namespace app\presentation\books\forms;

use yii\base\Model;

final class BookSearchForm extends Model
{
    public string $globalSearch = '';

    #[\Override]
    public function rules(): array
    {
        return [
            [['globalSearch'], 'string', 'min' => 2],
            [['globalSearch'], 'trim'],
        ];
    }

    #[\Override]
    public function formName(): string
    {
        return '';
    }
}
