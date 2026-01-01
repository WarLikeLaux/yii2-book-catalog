<?php

declare(strict_types=1);

namespace app\presentation\books\forms;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\BookRepositoryInterface;
use app\presentation\books\validators\IsbnValidator;
use app\presentation\common\forms\RepositoryAwareForm;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Yii;
use yii\web\Request;
use yii\web\UploadedFile;

final class BookForm extends RepositoryAwareForm
{
    /** @var int|string|null */
    public $id;

    /** @var string */
    public $title = '';

    /** @var int|string|null */
    public $year;

    /** @var string|null */
    public $description;

    /** @var string|int|null */
    public $isbn = '';

    /** @var array<int>|string|null */
    public $authorIds = [];

    /** @var \yii\web\UploadedFile|string|null */
    public $cover;

    public int $version = 1;

    #[CodeCoverageIgnore]
    public function loadFromRequest(Request $request): bool
    {
        $isLoaded = $this->load((array)$request->post());
        $this->cover = UploadedFile::getInstance($this, 'cover');

        return $isLoaded || $this->cover !== null;
    }

    #[\Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn', 'authorIds'], 'required'],
            [['year'], 'integer', 'min' => 1000, 'max' => (int)date('Y') + 1],
            [['description'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['isbn'], 'string', 'max' => 20],
            [['isbn'], IsbnValidator::class],
            [['isbn'], 'validateIsbnUnique'],
            [['authorIds'], 'each', 'rule' => ['integer']],
            [['authorIds'], 'validateAuthorsExist'],
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
    #[CodeCoverageIgnore]
    public function attributeLabels(): array
    {
        return [
            'title' => Yii::t('app', 'ui.title'),
            'year' => Yii::t('app', 'ui.year'),
            'description' => Yii::t('app', 'ui.description'),
            'isbn' => Yii::t('app', 'ui.isbn'),
            'cover' => Yii::t('app', 'ui.cover'),
            'authorIds' => Yii::t('app', 'ui.authors'),
        ];
    }

    public function validateIsbnUnique(string $attribute): void
    {
        $value = $this->$attribute;

        if (!is_string($value)) {
            return; // @codeCoverageIgnore
        }

        $excludeId = $this->id !== null ? (int)$this->id : null;
        $repository = $this->resolve(BookRepositoryInterface::class);

        if (!$repository->existsByIsbn($value, $excludeId)) {
            return;
        }

        $this->addError($attribute, Yii::t('app', 'book.error.isbn_exists_generic'));
    }

    public function validateAuthorsExist(string $attribute): void
    {
        $value = $this->$attribute;

        if (!is_array($value)) {
            return; // @codeCoverageIgnore
        }

        $repository = $this->resolve(AuthorRepositoryInterface::class);

        foreach ($value as $authorId) {
            if (!is_int($authorId) && !is_string($authorId)) {
                continue; // @codeCoverageIgnore
            }

            $authorId = (int)$authorId;

            if ($repository->findById($authorId) instanceof AuthorReadDto) {
                continue;
            }

            $this->addError($attribute, Yii::t('app', 'author.error.id_not_found', ['id' => $authorId]));
        }
    }
}
