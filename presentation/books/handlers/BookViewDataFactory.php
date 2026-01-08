<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\QueryResult;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookFinderInterface;
use app\application\ports\BookSearcherInterface;
use app\presentation\books\forms\BookForm;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;
use AutoMapper\AutoMapperInterface;
use yii\data\DataProviderInterface;
use yii\web\NotFoundHttpException;

final readonly class BookViewDataFactory
{
    /**
     * Initialize the factory with required services for finding, searching, mapping, paging, and resolving file URLs.
     */
    public function __construct(
        private BookFinderInterface $finder,
        private BookSearcherInterface $searcher,
        private AuthorQueryServiceInterface $authorQueryService,
        private AutoMapperInterface $autoMapper,
        private PagedResultDataProviderFactory $dataProviderFactory,
        private FileUrlResolver $resolver,
    ) {
    }

    public function getIndexDataProvider(int $page, int $pageSize): DataProviderInterface
    {
        $queryResult = $this->searcher->search('', $page, $pageSize);

        $dtos = array_map(
            fn(mixed $dto): BookReadDto => $dto instanceof BookReadDto
                ? $this->withResolvedUrl($dto)
                : throw new \LogicException('Expected BookReadDto'),
            $queryResult->getModels(),
        );

        $newResult = new QueryResult(
            $dtos,
            $queryResult->getTotalCount(),
            $queryResult->getPagination(),
        );

        return $this->dataProviderFactory->create($newResult);
    }

    /**
     * Prepare a BookForm populated with data for the book identified by the given id.
     *
     * @param int $id The book identifier.
     * @return BookForm The form populated with the book's data.
     * @throws NotFoundHttpException If no book with the given id is found.
     */
    public function getBookForUpdate(int $id): BookForm
    {
        $dto = $this->finder->findById($id);

        if (!$dto instanceof BookReadDto) {
             throw new NotFoundHttpException();
        }

        /** @var BookForm */
        return $this->autoMapper->map($dto, BookForm::class);
    }

    /**
     * Retrieve a book by id for viewing and return its DTO with the cover URL resolved.
     *
     * @param int $id The book identifier.
     * @return BookReadDto The book read DTO with the cover URL resolved.
     * @throws NotFoundHttpException If no book with the given id exists.
     */
    public function getBookView(int $id): BookReadDto
    {
        $dto = $this->finder->findById($id);

        if (!$dto instanceof BookReadDto) {
             throw new NotFoundHttpException();
        }

        return $this->withResolvedUrl($dto);
    }

    /**
     * Provide a map of author IDs to their full names.
     *
     * @return array<int,string> Keys are author IDs, values are author full names.
     */
    public function getAuthorsList(): array
    {
        $authors = $this->authorQueryService->findAllOrderedByFio();
        $map = [];

        foreach ($authors as $author) {
            $map[$author->id] = $author->fio;
        }

        return $map;
    }

    /**
     * Create a BookReadDto with the cover URL resolved.
     *
     * The returned DTO contains the same data as the input but with `coverUrl`
     * replaced by the value produced by the file URL resolver.
     *
     * @param BookReadDto $dto The source DTO.
     * @return BookReadDto The new DTO with a resolved `coverUrl`.
     */
    private function withResolvedUrl(BookReadDto $dto): BookReadDto
    {
        return new BookReadDto(
            $dto->id,
            $dto->title,
            $dto->year,
            $dto->description,
            $dto->isbn,
            $dto->authorIds,
            $dto->authorNames,
            $this->resolver->resolve($dto->coverUrl),
            $dto->isPublished,
            $dto->version,
        );
    }
}