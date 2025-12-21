<?php

declare(strict_types=1);

namespace app\models\forms;

use app\repositories\AuthorReadRepository;
use yii\base\Model;
use yii\web\Request;

final class AuthorSearchForm extends Model
{
    public string $q = '';
    public int $page = 1;
    public int $pageSize = 20;

    public function rules(): array
    {
        return [
            ['q', 'trim'],
            ['q', 'string', 'max' => 255],
            ['page', 'integer', 'min' => 1],
            ['pageSize', 'integer', 'min' => 1, 'max' => 50],
        ];
    }

    public function formName(): string
    {
        return '';
    }
}
