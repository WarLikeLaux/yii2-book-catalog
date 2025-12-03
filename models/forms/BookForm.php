<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\Book;
use app\validators\IsbnValidator;
use yii\base\Model;
use yii\web\UploadedFile;

final class BookForm extends Model
{
    public ?int $id = null;
    public string $title = '';
    public ?int $year = null;
    public string $description = '';
    public string $isbn = '';
    public array $authorIds = [];
    public UploadedFile|string|null $cover = null;

    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn', 'authorIds'], 'required'],
            [['year'], 'integer', 'min' => 1000, 'max' => (int)date('Y') + 1],
            [['description'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['isbn'], 'string', 'max' => 20],
            [['isbn'], IsbnValidator::class],
            [
                ['isbn'],
                'unique',
                'targetClass' => Book::class,
                'filter' => fn($query) => $this->id ? $query->andWhere(['<>', 'id', $this->id]) : $query,
            ],
            [['authorIds'], 'each', 'rule' => ['integer']],
            [
                ['cover'],
                'file',
                'skipOnEmpty' => true,
                'extensions' => 'png, jpg, jpeg',
                'maxSize' => 5 * 1024 * 1024,
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Название',
            'year' => 'Год выпуска',
            'description' => 'Описание',
            'isbn' => 'ISBN',
            'cover' => 'Обложка',
            'authorIds' => 'Авторы',
        ];
    }
}
