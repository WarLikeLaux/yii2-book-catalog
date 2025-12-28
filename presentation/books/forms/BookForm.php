<?php

declare(strict_types=1);

namespace app\presentation\books\forms;

use app\presentation\books\validators\AuthorExistsValidator;
use app\presentation\books\validators\IsbnValidator;
use app\presentation\books\validators\UniqueIsbnValidator;
use Yii;
use yii\base\Model;
use yii\web\Request;
use yii\web\UploadedFile;

final class BookForm extends Model
{
    /** @var int|string|null */
    public $id;

    /** @var string */
    public $title = '';

    /** @var int|string|null */
    public $year;

    /** @var string|null */
    public $description;

    /** @var string */
    public $isbn = '';

    /** @var array<int> */
    public $authorIds = [];

    /** @var UploadedFile|string|null */
    public $cover;

    /** @codeCoverageIgnore Работает с Yii UploadedFile::getInstance */
    public function loadFromRequest(Request $request): bool
    {
        $isLoaded = $this->load((array)$request->post());
        $this->cover = UploadedFile::getInstance($this, 'cover');

        return $isLoaded || $this->cover !== null;
    }

    #[\Override]
    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn', 'authorIds'], 'required'],
            [['year'], 'integer', 'min' => 1000, 'max' => (int)date('Y') + 1],
            [['description'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['isbn'], 'string', 'max' => 20],
            [['isbn'], IsbnValidator::class],
            [['isbn'], UniqueIsbnValidator::class],
            [['authorIds'], 'each', 'rule' => ['integer']],
            [['authorIds'], AuthorExistsValidator::class],
            [
                ['cover'],
                'file',
                'skipOnEmpty' => true,
                'extensions' => 'png, jpg, jpeg',
                'maxSize' => 5 * 1024 * 1024,
            ],
        ];
    }

    #[\Override]
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
