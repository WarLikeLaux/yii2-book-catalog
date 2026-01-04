<?php

declare(strict_types=1);

namespace app\presentation\books\forms;

use app\application\ports\AuthorQueryServiceInterface;
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
            [['version'], 'integer', 'min' => 1],
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

        $this->addError($attribute, Yii::t('app', 'book.error.isbn_exists'));
    }

    public function validateAuthorsExist(string $attribute): void
    {
        $value = $this->$attribute;

        if (!is_array($value)) {
            return; // @codeCoverageIgnore
        }

        $ids = [];

        foreach ($value as $rawId) {
            if (!is_int($rawId) && !is_string($rawId)) {
                continue; // @codeCoverageIgnore
            }

            $id = (int)$rawId;

            if ($id <= 0) {
                continue;
            }

            $ids[] = $id;
        }

        if ($ids === []) {
            return;
        }

        $service = $this->resolve(AuthorQueryServiceInterface::class);
        $missingIds = $service->findMissingIds($ids);

        foreach ($missingIds as $missingId) {
            $this->addError($attribute, Yii::t('app', 'author.error.id_not_found', ['id' => $missingId]));
        }
    }

    /**
     * @param array<int, string> $authors
     * @return array<int, string>
     */
    public function getAuthorInitValueText(array $authors): array
    {
        $authorIds = $this->normalizeAuthorIds();

        if ($authorIds === []) {
            return [];
        }

        return array_map(
            static fn(int $authorId): string => $authors[$authorId] ?? (string)$authorId,
            $authorIds
        );
    }

    /**
     * @return array<int>
     */
    private function normalizeAuthorIds(): array
    {
        $authorIds = $this->authorIds;

        if (!is_array($authorIds)) {
            $authorIds = $authorIds === null ? [] : [$authorIds];
        }

        $normalized = [];

        foreach ($authorIds as $authorId) {
            $id = filter_var($authorId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($id === false) {
                continue;
            }

            $normalized[] = $id;
        }

        return $normalized;
    }
}
