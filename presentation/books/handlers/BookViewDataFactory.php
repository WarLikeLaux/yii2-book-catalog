<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\authors\queries\AuthorQueryService;
use app\application\books\queries\BookReadDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookFormMapper;
use app\presentation\books\viewmodels\BookViewModel;
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

        $viewModels = array_map(
            fn(mixed $dto): BookViewModel => $dto instanceof BookReadDto
                ? new BookViewModel($dto, $this->resolver)
                : throw new \LogicException('Expected BookReadDto'),
            $queryResult->getModels()
        );

        $newResult = new QueryResult(
            $viewModels,
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

    public function getBookView(int $id): BookViewModel
    {
        $dto = $this->bookQueryService->findById($id);
        if (!$dto instanceof BookReadDto) {
             throw new NotFoundHttpException();
        }
        return new BookViewModel($dto, $this->resolver);
    }

    /**
     * @return array<int, string>
     */
    public function getAuthorsList(): array
    {
        return $this->authorQueryService->getAuthorsMap();
    }
}
