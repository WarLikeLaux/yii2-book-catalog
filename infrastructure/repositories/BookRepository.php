<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\PagedResultInterface;
use app\application\ports\TranslatorInterface;
use app\domain\entities\Book as BookEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\StaleDataException;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use RuntimeException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\IntegrityException;
use yii\db\StaleObjectException;

final readonly class BookRepository implements BookRepositoryInterface
{
    use DatabaseExceptionHandlerTrait;

    public function __construct(
        private Connection $db,
        private TranslatorInterface $translator
    ) {
    }

    public function save(BookEntity $book): void
    {
        if ($book->getId() === null) {
            $ar = new Book();
        } else {
            $ar = Book::findOne($book->getId());
            if ($ar === null) {
                throw new EntityNotFoundException($this->translator->translate('app', 'Book not found'));
            }
            $ar->version = $book->getVersion();
        }

        $ar->title = $book->getTitle();
        $ar->year = $book->getYear()->value;
        $ar->isbn = $book->getIsbn()->value;
        $ar->description = $book->getDescription();
        $ar->cover_url = $book->getCoverUrl();
        $ar->is_published = (int)$book->isPublished();

        if ($this->existsByIsbn($book->getIsbn()->value, $book->getId())) {
            throw new AlreadyExistsException(
                $this->translator->translate('app', 'Book with ISBN {isbn} already exists', ['isbn' => $book->getIsbn()->value]),
                409
            );
        }

        $this->persistBook($ar, $book->getIsbn()->value);

        if ($book->getId() === null) {
            $book->setId($ar->id);
        }

        $book->incrementVersion();
        $this->persistAuthorChanges($book);
        $book->markAuthorsPersisted();
    }

    public function get(int $id): BookEntity
    {
        $ar = Book::find()->where(['id' => $id])->with('authors')->one();
        if ($ar === null) {
            throw new EntityNotFoundException($this->translator->translate('app', 'Book not found'));
        }

        /** @var Author[] $authors */
        $authors = $ar->authors;
        $authorIds = array_map(fn(Author $a) => $a->id, $authors);

        return BookEntity::reconstitute(
            id: $ar->id,
            title: $ar->title,
            year: new BookYear($ar->year),
            isbn: new Isbn($ar->isbn),
            description: $ar->description,
            coverUrl: $ar->cover_url,
            authorIds: $authorIds,
            published: (bool)$ar->is_published,
            version: $ar->version
        );
    }

    public function delete(BookEntity $book): void
    {
        $ar = Book::findOne($book->getId());
        if ($ar === null) {
            throw new EntityNotFoundException($this->translator->translate('app', 'Book not found'));
        }

        if ($ar->delete() === false) {
            throw new RuntimeException($this->translator->translate('app', 'Failed to delete book')); // @codeCoverageIgnore
        }
    }

    public function findById(int $id): ?BookReadDto
    {
        $book = Book::findOne($id);
        if ($book === null) {
            return null;
        }

        return $this->mapToDto($book);
    }

    public function findByIdWithAuthors(int $id): ?BookReadDto
    {
        $book = Book::find()->byId($id)->withAuthors()->one();
        if ($book === null) {
            return null;
        }

        return $this->mapToDto($book);
    }

    public function search(string $term, int $page, int $pageSize): PagedResultInterface
    {
        $query = Book::find()->withAuthors()->orderedByCreatedAt();

        if ($term !== '') {
            $this->applySearchConditions($query, $term);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);

        $models = array_map(
            $this->mapToDto(...),
            $dataProvider->getModels()
        );

        $totalCount = $dataProvider->getTotalCount();
        $totalPages = (int)ceil($totalCount / $pageSize);

        $pagination = new PaginationDto(
            page: $page,
            pageSize: $pageSize,
            totalCount: $totalCount,
            totalPages: $totalPages
        );

        return new QueryResult(
            models: $models,
            totalCount: $totalCount,
            pagination: $pagination
        );
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
    private function persistBook(Book $ar, string $isbn): void
    {
        try {
            if (!$ar->save()) {
                $errors = $ar->getFirstErrors();
                $message = $errors !== [] ? array_shift($errors) : $this->translator->translate('app', 'Failed to save book');
                throw new RuntimeException($message);
            }
        } catch (StaleObjectException) {
            throw new StaleDataException();
        } catch (IntegrityException $e) {
            if ($this->isDuplicateError($e)) {
                throw new AlreadyExistsException(
                    $this->translator->translate('app', 'Book with ISBN {isbn} already exists', ['isbn' => $isbn]),
                    409,
                    $e
                );
            }
            throw $e;
        }
    }

    private function persistAuthorChanges(BookEntity $book): void
    {
        $bookId = $book->getId();
        if ($bookId === null) {
            return; // @codeCoverageIgnore
        }

        $toDelete = $book->getRemovedAuthorIds();
        $toAdd = $book->getAddedAuthorIds();

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

    private function mapToDto(Book $book): BookReadDto
    {
        $authorNames = [];
        foreach ($book->authors as $author) {
            $authorNames[$author->id] = $author->fio;
        }

        return new BookReadDto(
            id: $book->id,
            title: $book->title,
            year: $book->year,
            description: $book->description,
            isbn: $book->isbn,
            authorIds: array_keys($authorNames),
            authorNames: $authorNames,
            coverUrl: $book->cover_url,
            isPublished: (bool)$book->is_published,
            version: $book->version
        );
    }

    private function applySearchConditions(ActiveQuery $query, string $term): void
    {
        $conditions = ['or'];

        if (preg_match('/^\d{4}$/', $term) === 1) {
            $conditions[] = ['year' => (int)$term];
        }

        $conditions[] = ['like', 'isbn', $term . '%', false];
        $conditions[] = $this->buildAuthorCondition($term);

        $fulltextQuery = $this->prepareFulltextQuery($term);
        if ($fulltextQuery !== '' && $fulltextQuery !== '0') {
            $conditions[] = new Expression(
                'MATCH(title, description) AGAINST(:query IN BOOLEAN MODE)',
                [':query' => $fulltextQuery]
            );
        }

        $query->andWhere($conditions);
    }

    private function prepareFulltextQuery(string $term): string
    {
        $term = (string)preg_replace('/[+\-><()~*\"@]+/', ' ', $term);
        $words = array_filter(explode(' ', trim($term)), fn($w): bool => $w !== '');

        return $words === [] ? '' : '+' . implode('* +', $words) . '*';
    }

    /**
     * @return array<mixed>
     */
    private function buildAuthorCondition(string $term): array
    {
        $subQuery = Author::find()
            ->select(new Expression('1'))
            ->innerJoin('book_authors ba', 'authors.id = ba.author_id')
            ->where('ba.book_id = books.id');

        $fulltextQuery = $this->prepareFulltextQuery($term);
        if ($fulltextQuery !== '' && $fulltextQuery !== '0') {
            $subQuery->andWhere(new Expression(
                'MATCH(authors.fio) AGAINST(:query IN BOOLEAN MODE)',
                [':query' => $fulltextQuery]
            ));
        }

        return ['exists', $subQuery];
    }
}
