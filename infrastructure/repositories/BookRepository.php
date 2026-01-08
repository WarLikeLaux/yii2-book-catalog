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

    public function __construct(
        private Connection $db,
        private ActiveRecordHydrator $hydrator,
    ) {
        parent::__construct();
        $this->authorSnapshots = new WeakMap();
    }

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
     * @throws \app\domain\exceptions\EntityNotFoundException
     * @throws \app\domain\exceptions\StaleDataException
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
     * @throws \app\domain\exceptions\EntityNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function delete(BookEntity $book): void
    {
        $this->deleteEntity($book, Book::class, DomainErrorCode::BookNotFound, 'book.error.delete_failed');
    }

    private function mapToEntity(Book $ar): BookEntity
    {
        /** @var Author[] $authors */
        $authors = $ar->authors;
        $authorIds = array_map(static fn(Author $a) => $a->id, $authors);

        return BookEntity::reconstitute(
            id: $ar->id,
            title: $ar->title,
            /** @reconstitution Доверяем данным из БД, обходим валидацию текущего года */
            year: new BookYear($ar->year),
            isbn: new Isbn($ar->isbn),
            description: $ar->description,
            coverImage: $ar->cover_url !== null ? new StoredFileReference($ar->cover_url) : null,
            authorIds: $authorIds,
            published: (bool)$ar->is_published,
            version: $ar->version,
        );
    }

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
     * @return int[]
     */
    private function getStoredAuthorIds(int $bookId): array
    {
        $ids = $this->db->createCommand(
            'SELECT author_id FROM book_authors WHERE book_id = :bookId',
        )->bindValue(':bookId', $bookId)->queryColumn();

        return array_map(intval(...), $ids);
    }

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
