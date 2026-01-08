<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\BookRepositoryInterface;
use app\domain\entities\Book as BookEntity;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\StaleDataException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use app\infrastructure\components\hydrator\ActiveRecordHydrator;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use WeakMap;
use yii\db\Connection;

final readonly class BookRepository extends BaseActiveRecordRepository implements BookRepositoryInterface
{
    use IdentityAssignmentTrait;

    /** @var WeakMap<BookEntity, array<int>> */
    private WeakMap $authorSnapshots;

    /**
     * Construct the repository with required services and initialize author snapshots.
     *
     * Initializes the base repository and a WeakMap used to track per-entity author ID snapshots for change detection.
     *
     * @param Connection $db Database connection used for queries and transactions.
     * @param ActiveRecordHydrator $hydrator Hydrator used to map between domain entities and ActiveRecord models.
     */
    public function __construct(
        private Connection $db,
        private ActiveRecordHydrator $hydrator,
    ) {
        parent::__construct();
        $this->authorSnapshots = new WeakMap();
    }

    /**
     * Persists the given BookEntity to storage and synchronizes its author relationships.
     *
     * @param BookEntity $book The book to save. If the book is new, an identifier is assigned and identity registered; if existing, its version is advanced.
     */
    public function save(BookEntity $book): void
    {
        $isNew = $book->id === null;
        $ar = $isNew ? new Book() : $this->getArForEntity($book, Book::class, DomainErrorCode::BookNotFound);
        $ar->version = $book->version;

        $this->hydrator->hydrate($ar, $book, [
            'title',
            'year',
            'isbn',
            'description',
            'cover_url' => static fn(BookEntity $e): ?string => $e->coverImage?->getPath(),
            'is_published' => static fn(BookEntity $e): int => $e->published ? 1 : 0,
        ]);

        $this->persist($ar, DomainErrorCode::BookIsbnExists, 'book.error.save_failed');

        if ($isNew) {
            $this->assignId($book, $ar->id); // @phpstan-ignore property.notFound
            $this->registerIdentity($book, $ar);
        } else {
            $book->incrementVersion();
        }

        $this->syncAuthors($book);
    }

    /**
     * Retrieve the book with the given ID, including its associated authors.
     *
     * Also registers the entity/AR identity and stores a snapshot of author IDs for later change detection.
     *
     * @param int $id The book identifier.
     * @return BookEntity The BookEntity reconstructed from persistence.
     * @throws EntityNotFoundException If no book with the given ID exists (DomainErrorCode::BookNotFound).
     */
    public function get(int $id): BookEntity
    {
        /** @var Book|null $ar */
        $ar = Book::find()->where(['id' => $id])->with('authors')->one();

        if ($ar === null) {
            throw new EntityNotFoundException(DomainErrorCode::BookNotFound);
        }

        $entity = $this->mapToEntity($ar);
        $this->registerIdentity($entity, $ar);
        $this->authorSnapshots[$entity] = $entity->authorIds;

        return $entity;
    }

    /**
     * Retrieve a BookEntity by its id and ensure its persisted version matches the expected version.
     *
     * @return \app\domain\entities\BookEntity The reconstituted BookEntity.
     * @throws \app\domain\exceptions\EntityNotFoundException If no book with the given id exists (DomainErrorCode::BookNotFound).
     * @throws \app\domain\exceptions\StaleDataException If the stored version does not equal the expected version.
     */
    public function getByIdAndVersion(int $id, int $expectedVersion): BookEntity
    {
        /** @var Book|null $ar */
        $ar = Book::find()->where(['id' => $id])->with('authors')->one();

        if ($ar === null) {
            throw new EntityNotFoundException(DomainErrorCode::BookNotFound);
        }

        if ($ar->version !== $expectedVersion) {
            throw new StaleDataException();
        }

        $entity = $this->mapToEntity($ar);
        $this->registerIdentity($entity, $ar);
        $this->authorSnapshots[$entity] = $entity->authorIds;

        return $entity;
    }

    /**
         * Removes the given book from persistent storage.
         *
         * @param BookEntity $book The book entity to delete.
         * @throws \app\domain\exceptions\EntityNotFoundException If the book cannot be found.
         * @throws \yii\base\InvalidConfigException If the repository or ActiveRecord configuration is invalid.
         */
    public function delete(BookEntity $book): void
    {
        $this->deleteEntity($book, Book::class, DomainErrorCode::BookNotFound, 'book.error.delete_failed');
    }

    /**
     * Convert a Book ActiveRecord (including its loaded authors relation) into a domain BookEntity.
     *
     * @param Book $ar Book ActiveRecord with its `authors` relation populated.
     * @return BookEntity The reconstituted BookEntity populated from AR fields and related author IDs.
     */
    private function mapToEntity(Book $ar): BookEntity
    {
        /** @var Author[] $authors */
        $authors = $ar->authors;
        $authorIds = array_map(static fn(Author $a) => $a->id, $authors);

        return BookEntity::reconstitute(
            id: $ar->id,
            title: $ar->title,
            year: new BookYear($ar->year),
            isbn: new Isbn($ar->isbn),
            description: $ar->description,
            coverImage: $ar->cover_url !== null ? new StoredFileReference($ar->cover_url) : null,
            authorIds: $authorIds,
            published: (bool)$ar->is_published,
            version: $ar->version,
        );
    }

    /**
     * Synchronizes the book's author associations in persistent storage to match the entity's `authorIds`.
     *
     * If the entity has no `id` the method is a no-op. When changes are detected, it removes associations
     * for authors no longer present on the entity and inserts associations for newly added authors.
     *
     * @param BookEntity $book The book entity whose `authorIds` will be persisted; must have an `id` to perform changes.
     */
    private function syncAuthors(BookEntity $book): void
    {
        $bookId = $book->id;

        if ($bookId === null) {
            return; // @codeCoverageIgnore
        }

        if (!$this->hasAuthorsChanged($book)) {
            return;
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
            static fn(int $authorId): array => [$bookId, $authorId],
            $toAdd,
        );
        $this->db->createCommand()->batchInsert(
            'book_authors',
            ['book_id', 'author_id'],
            $rows,
        )->execute();
    }

    /**
     * Retrieve the author IDs associated with the specified book.
     *
     * @param int $bookId The book's identifier.
     * @return int[] Author IDs associated with the book.
     */
    private function getStoredAuthorIds(int $bookId): array
    {
        $ids = $this->db->createCommand(
            'SELECT author_id FROM book_authors WHERE book_id = :bookId',
        )->bindValue(':bookId', $bookId)->queryColumn();

        return array_map(intval(...), $ids);
    }

    /**
     * Determines whether the book's author list differs from the last stored snapshot.
     *
     * If no snapshot exists for the given book, this method treats the authors as changed and returns `true`.
     *
     * @param BookEntity $book The book whose current author IDs should be compared to the stored snapshot.
     * @return bool `true` if the current author IDs differ from the snapshot or no snapshot exists, `false` otherwise.
     */
    private function hasAuthorsChanged(BookEntity $book): bool
    {
        if (!isset($this->authorSnapshots[$book])) {
            return true;
        }

        $current = $book->authorIds;
        $snapshot = $this->authorSnapshots[$book];

        $currentSorted = [...$current];
        $snapshotSorted = [...$snapshot];

        sort($currentSorted);
        sort($snapshotSorted);

        return $currentSorted !== $snapshotSorted;
    }
}