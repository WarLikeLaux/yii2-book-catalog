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

    public function getBookForUpdate(int $id): BookForm
    {
        $dto = $this->finder->findById($id);

        if (!$dto instanceof BookReadDto) {
             throw new NotFoundHttpException();
        }

        /** @var BookForm */
        return $this->autoMapper->map($dto, BookForm::class);
    }

    public function getBookView(int $id): BookReadDto
    {
        $dto = $this->finder->findById($id);

        if (!$dto instanceof BookReadDto) {
             throw new NotFoundHttpException();
        }

        return $this->withResolvedUrl($dto);
    }

    /**
     * @return array<int, string>
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
