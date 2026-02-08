<?php

declare(strict_types=1);

namespace app\presentation\books\forms;

use app\application\common\values\AuthorIdCollection;
use app\presentation\books\validators\IsbnValidator;
use Override;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
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

    /** @var string|int|null */
    public $isbn = '';

    /** @var array<int>|string|null */
    public $authorIds = [];

    /** @var UploadedFile|string|null */
    public $cover;
    public int $version = 1;

    #[CodeCoverageIgnore]
    public function loadFromRequest(Request $request): bool
    {
        $isLoaded = $this->load((array)$request->post());
        $this->cover = UploadedFile::getInstance($this, 'cover');

        return $isLoaded || $this->cover !== null;
    }

    #[Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn', 'authorIds'], 'required'],
            [['year'], 'integer', 'min' => 1000, 'max' => (int)date('Y') + 5],
            [['description'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['isbn'], 'string', 'max' => 20],
            [['isbn'], IsbnValidator::class],
            [['authorIds'], 'each', 'rule' => ['integer']],
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

    #[Override]
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

    /**
     * @param array<int, string> $authors
     * @return array<int, string>
     */
    public function getAuthorInitValueText(array $authors): array
    {
        $authorIds = AuthorIdCollection::fromMixed($this->authorIds)->toArray();

        if ($authorIds === []) {
            return [];
        }

        return array_map(
            static fn(int $authorId): string => $authors[$authorId] ?? (string)$authorId,
            $authorIds,
        );
    }
}
