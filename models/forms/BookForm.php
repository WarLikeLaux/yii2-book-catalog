<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\Author;
use app\models\Book;
use app\validators\IsbnValidator;
use Yii;
use yii\base\Model;
use yii\web\Request;
use yii\web\UploadedFile;

final class BookForm extends Model
{
    /** @var int|string|null */
    public $id = null;

    /** @var string */
    public $title = '';

    /** @var int|string|null */
    public $year = null;

    /** @var string */
    public $description = '';

    /** @var string */
    public $isbn = '';

    /** @var array|string */
    public $authorIds = [];

    /** @var UploadedFile|string|null */
    public $cover = null;

    public function loadFromRequest(Request $request): bool
    {
        $isLoaded = $this->load($request->post());
        $this->cover = UploadedFile::getInstance($this, 'cover');

        return $isLoaded || $this->cover !== null;
    }

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
                ['authorIds'],
                'each',
                'rule' => ['exist', 'targetClass' => Author::class, 'targetAttribute' => 'id'],
            ],
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
            'title' => Yii::t('app', 'Title'),
            'year' => Yii::t('app', 'Year'),
            'description' => Yii::t('app', 'Description'),
            'isbn' => Yii::t('app', 'ISBN'),
            'cover' => Yii::t('app', 'Cover'),
            'authorIds' => Yii::t('app', 'Authors'),
        ];
    }
}
