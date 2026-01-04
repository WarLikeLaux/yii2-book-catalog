<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\authors\queries\AuthorQueryService;
use app\application\books\queries\BookReadDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookFormMapper;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;
use yii\data\DataProviderInterface;
use yii\web\NotFoundHttpException;

final readonly class BookViewDataFactory
{
    public function __construct(
        private BookQueryServiceInterface $bookQueryService,
        private AuthorQueryService $authorQueryService,
        private BookFormMapper $mapper,
        private PagedResultDataProviderFactory $dataProviderFactory,
        private FileUrlResolver $resolver
    ) {
    }

    public function getIndexDataProvider(int $page, int $pageSize): DataProviderInterface
    {
        $queryResult = $this->bookQueryService->search('', $page, $pageSize);

        $dtos = array_map(
            fn(mixed $dto): BookReadDto => $dto instanceof BookReadDto
                ? $this->withResolvedUrl($dto)
                : throw new \LogicException('Expected BookReadDto'),
            $queryResult->getModels()
        );

        $newResult = new QueryResult(
            $dtos,
            $queryResult->getTotalCount(),
            $queryResult->getPagination()
        );

        return $this->dataProviderFactory->create($newResult);
    }

    public function getBookForUpdate(int $id): BookForm
    {
        $dto = $this->bookQueryService->findById($id);

        if (!$dto instanceof BookReadDto) {
             throw new NotFoundHttpException();
        }

        return $this->mapper->toForm($dto);
    }

    public function getBookView(int $id): BookReadDto
    {
        $dto = $this->bookQueryService->findById($id);

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
        return $this->authorQueryService->getAuthorsMap();
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
            $dto->version
        );
    }
}
