<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\BookRepositoryInterface;
use app\domain\entities\Book as BookEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\StaleDataException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use DateTimeImmutable;
use ReflectionMethod;
use RuntimeException;
use yii\db\Connection;
use yii\db\IntegrityException;
use yii\db\StaleObjectException;

final readonly class BookRepository implements BookRepositoryInterface
{
    use DatabaseExceptionHandlerTrait;

    public function __construct(
        private Connection $db
    ) {
    }

    public function save(BookEntity $book): void
    {
        $isNew = $book->id === null;
        if ($isNew) {
            $ar = new Book();
            $ar->version = $book->version;
        } else {
            $ar = Book::findOne($book->id);
            if ($ar === null) {
                throw new EntityNotFoundException('book.error.not_found');
            }
            $ar->version = $book->version;
        }

        $ar->title = $book->title;
        $ar->year = $book->year->value;
        $ar->isbn = $book->isbn->value;
        $ar->description = $book->description;
        $ar->cover_url = $book->coverImage?->getPath();
        $ar->is_published = (int)$book->published;

        if ($this->existsByIsbn($book->isbn->value, $book->id)) {
            throw new AlreadyExistsException('book.error.isbn_exists', 409);
        }

        $this->persistBook($ar);

        if ($isNew) {
            $this->assignBookId($book, $ar->id);
        } else {
            $book->incrementVersion();
        }

        $this->syncAuthors($book);
    }

    public function get(int $id): BookEntity
    {
        $ar = Book::find()->where(['id' => $id])->with('authors')->one();
        if ($ar === null) {
            throw new EntityNotFoundException('book.error.not_found');
        }

        /** @var Author[] $authors */
        $authors = $ar->authors;
        $authorIds = array_map(fn(Author $a) => $a->id, $authors);

        return BookEntity::reconstitute(
            id: $ar->id,
            title: $ar->title,
            /** @reconstitution Валидация времени не требуется, так как данные загружаются из хранилища */
            year: new BookYear($ar->year, new DateTimeImmutable()),
            isbn: new Isbn($ar->isbn),
            description: $ar->description,
            coverImage: $ar->cover_url !== null ? new StoredFileReference($ar->cover_url) : null,
            authorIds: $authorIds,
            published: (bool)$ar->is_published,
            version: $ar->version
        );
    }

    public function delete(BookEntity $book): void
    {
        $ar = Book::findOne($book->id);
        if ($ar === null) {
            throw new EntityNotFoundException('book.error.not_found');
        }

        if ($ar->delete() === false) {
            throw new RuntimeException('book.error.delete_failed'); // @codeCoverageIgnore
        }
    }

    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool
    {
        $query = Book::find()->andWhere(['isbn' => $isbn]);

        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    /** @codeCoverageIgnore Защитный код (недостижим из-за валидации домена) */
    private function persistBook(Book $ar): void
    {
        try {
            if (!$ar->save()) {
                $errors = $ar->getFirstErrors();
                $message = $errors !== [] ? array_shift($errors) : 'book.error.save_failed';
                throw new RuntimeException($message);
            }
        } catch (StaleObjectException) {
            throw new StaleDataException();
        } catch (IntegrityException $e) {
            if ($this->isDuplicateError($e)) {
                throw new AlreadyExistsException('book.error.isbn_exists', 409, $e);
            }
            throw $e;
        }
    }

    private function assignBookId(BookEntity $book, int $id): void
    {
        $method = new ReflectionMethod(BookEntity::class, 'setId');
        $method->invoke($book, $id);
    }

    private function syncAuthors(BookEntity $book): void
    {
        $bookId = $book->id;
        if ($bookId === null) {
            return; // @codeCoverageIgnore
        }

        $storedAuthorIds = $this->getStoredAuthorIds($bookId);
        $currentAuthorIds = $book->authorIds;

        $toDelete = array_values(array_diff($storedAuthorIds, $currentAuthorIds));
        $toAdd = array_values(array_diff($currentAuthorIds, $storedAuthorIds));
        sort($toDelete);
        sort($toAdd);

        if ($toDelete !== []) {
            $this->db->createCommand()->delete('book_authors', [
                'and',
                ['book_id' => $bookId],
                ['in', 'author_id', $toDelete],
            ])->execute();
        }

        if ($toAdd === []) {
            return;
        }

        $rows = array_map(
            fn(int $authorId): array => [$bookId, $authorId],
            $toAdd
        );
        $this->db->createCommand()->batchInsert(
            'book_authors',
            ['book_id', 'author_id'],
            $rows
        )->execute();
    }

    /**
     * @return int[]
     */
    private function getStoredAuthorIds(int $bookId): array
    {
        $ids = $this->db->createCommand(
            'SELECT author_id FROM book_authors WHERE book_id = :bookId'
        )->bindValue(':bookId', $bookId)->queryColumn();

        return array_map(intval(...), $ids);
    }
}
