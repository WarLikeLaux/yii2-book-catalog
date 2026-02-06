<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationRequest;
use app\application\common\dto\QueryResult;
use app\application\ports\BookSearcherInterface;
use app\presentation\books\dto\BookListViewModel;
use app\presentation\books\dto\BookViewModel;
use app\presentation\books\mappers\BookViewModelMapper;
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\common\dto\CrudPaginationRequest;
use LogicException;
use yii\data\DataProviderInterface;
use yii\web\Request;

final readonly class BookListViewFactory
{
    public function __construct(
        private BookSearcherInterface $searcher,
        private PagedResultDataProviderFactory $dataProviderFactory,
        private BookDtoUrlResolver $urlResolver,
        private BookViewModelMapper $viewModelMapper,
    ) {
    }

    public function getListViewModel(Request $request): BookListViewModel
    {
        $pagination = CrudPaginationRequest::fromRequest($request);

        return new BookListViewModel(
            $this->getIndexDataProvider($pagination),
        );
    }

    private function getIndexDataProvider(PaginationRequest $pagination): DataProviderInterface
    {
        $queryResult = $this->searcher->search('', $pagination->page, $pagination->limit);

        $dtos = array_map(
            fn(mixed $dto): BookViewModel => $dto instanceof BookReadDto
                ? $this->viewModelMapper->map($this->urlResolver->resolveUrl($dto))
                : throw new LogicException('Expected BookReadDto'),
            $queryResult->getModels(),
        );

        $newResult = new QueryResult(
            $dtos,
            $queryResult->getTotalCount(),
            $queryResult->getPagination(),
        );

        return $this->dataProviderFactory->create($newResult);
    }
}
