<?php

declare(strict_types=1);

namespace app\presentation\authors\forms;

use Override;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use yii\base\Model;

final class AuthorSearchForm extends Model
{
    public string $q = '';
    public int $page = 1;
    public int $pageSize = 20;

    #[Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            ['q', 'trim'],
            ['q', 'string', 'max' => 255],
            ['page', 'integer', 'min' => 1],
            ['pageSize', 'integer', 'min' => 1, 'max' => 50],
        ];
    }

    #[Override]
    #[CodeCoverageIgnore]
    public function formName(): string
    {
        return '';
    }
}
