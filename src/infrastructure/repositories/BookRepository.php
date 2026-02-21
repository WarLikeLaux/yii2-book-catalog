<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\BookRepositoryInterface;
use app\domain\entities\Book as BookEntity;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\StaleDataException;
use app\domain\values\BookStatus;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use app\infrastructure\components\hydrator\ActiveRecordHydrator;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use RuntimeException;
use WeakMap;
use yii\base\InvalidConfigException;
use yii\db\Connection;

final readonly class BookRepository extends BaseActiveRecordRepository implements BookRepositoryInterface
{
    use IdentityAssignmentTrait;

    /** @var WeakMap<BookEntity, array<int>> */
    private WeakMap $authorSnapshots;

    public function __construct(
        private Connection $db,
        private ActiveRecordHydrator $hydrator,
    ) {
        parent::__construct();
        $this->authorSnapshots = new WeakMap();
    }

    public function save(BookEntity $book): int
    {
        /** @var int */
        return $this->db->transaction(function () use ($book): int {
            $isNew = $book->getId() === null;
            $model = $isNew ? new Book() : $this->getArForEntity($book, Book::class, DomainErrorCode::BookNotFound);
            $model->version = $book->version;

            $this->hydrator->hydrate($model, $book, [
                'title',
                'year',
                'isbn',
                'description',
                'cover_url' => static fn(BookEntity $e): ?string => $e->coverImage?->getPath(),
                'status' => static fn(BookEntity $e): string => $e->status->value,
            ]);

            $this->persist($model, DomainErrorCode::BookStaleData, DomainErrorCode::BookIsbnExists);

            if ($isNew) {
                if ($model->id === null) {
                    throw new RuntimeException('Failed to get ID for new book'); // @codeCoverageIgnore
                }

                $this->assignId($book, $model->id);
            } else {
                $book->incrementVersion();
            }

            $this->registerIdentity($book, $model);

            $this->syncAuthors($book);

            return (int)$model->id;
        });
    }

    public function get(int $id): BookEntity
    {
        $ar = $this->getArWithAuthors($id);

        if (!$ar instanceof Book) {
            throw new EntityNotFoundException(DomainErrorCode::BookNotFound);
        }

        $entity = $this->mapToEntity($ar);
        $this->registerIdentity($entity, $ar);
        $this->updateAuthorSnapshot($entity);

        return $entity;
    }

    /**
     * @throws EntityNotFoundException
     * @throws StaleDataException
     */
    public function getByIdAndVersion(int $id, int $expectedVersion): BookEntity
    {
        $ar = $this->getArWithAuthors($id);

        if (!$ar instanceof Book) {
            throw new EntityNotFoundException(DomainErrorCode::BookNotFound);
        }

        if ($ar->version !== $expectedVersion) {
            throw new StaleDataException(DomainErrorCode::BookStaleData);
        }

        $entity = $this->mapToEntity($ar);
        $this->registerIdentity($entity, $ar);
        $this->updateAuthorSnapshot($entity);

        return $entity;
    }

    /**
     * @throws EntityNotFoundException
     * @throws InvalidConfigException
     */
    public function delete(BookEntity $book): void
    {
        $this->deleteEntity($book, Book::class, DomainErrorCode::BookNotFound);
    }

    private function getArWithAuthors(int $id): ?Book
    {
        /** @var Book|null $ar */
        $ar = Book::find()->where(['id' => $id])->with('authors')->one();

        return $ar;
    }

    private function mapToEntity(Book $ar): BookEntity
    {
        /** @var Author[] $authors */
        $authors = $ar->authors;
        $authorIds = array_map(static fn(Author $a): int => (int)$a->id, $authors);

        return BookEntity::reconstitute(
            id: (int)$ar->id,
            title: $ar->title,
            year: new BookYear($ar->year),
            isbn: new Isbn($ar->isbn),
            description: $ar->description,
            coverImage: $ar->cover_url !== null ? new StoredFileReference($ar->cover_url) : null,
            authorIds: $authorIds,
            status: BookStatus::from($ar->status),
            version: $ar->version,
        );
    }

    private function syncAuthors(BookEntity $book): void
    {
        $bookId = $book->getId();

        if ($bookId === null) {
            return; // @codeCoverageIgnore
        }

        if (!$this->hasAuthorsChanged($book)) {
            return;
        }

        $this->syncManyToMany($this->db, 'book_authors', 'book_id', 'author_id', $bookId, $book->authorIds);
        $this->updateAuthorSnapshot($book);
    }

    private function hasAuthorsChanged(BookEntity $book): bool
    {
        if (!isset($this->authorSnapshots[$book])) {
            return true;
        }

        $current = $book->authorIds;
        $snapshotSorted = $this->authorSnapshots[$book];

        $currentSorted = [...$current];
        sort($currentSorted);

        return $currentSorted !== $snapshotSorted;
    }

    private function updateAuthorSnapshot(BookEntity $book): void
    {
        $ids = $book->authorIds;
        sort($ids);
        $this->authorSnapshots[$book] = $ids;
    }
}
