<?php

declare(strict_types=1);

namespace app\presentation\books\forms;

use Override;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use yii\base\Model;

final class BookFilterForm extends Model
{
    public string $title = '';
    public string $isbn = '';
    public string $status = '';
    public string $author = '';

    /** @var int|string|null */
    public $year;

    #[Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            [['title', 'isbn', 'status', 'author'], 'trim'],
            [['title', 'isbn', 'status', 'author'], 'string'],
            ['year', 'integer'],
        ];
    }

    #[Override]
    #[CodeCoverageIgnore]
    public function formName(): string
    {
        return '';
    }
}
