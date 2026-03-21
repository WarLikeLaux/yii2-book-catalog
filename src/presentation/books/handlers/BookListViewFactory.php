<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\factories\BookSearchSpecificationFactory;
use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationRequest;
use app\application\common\dto\QueryResult;
use app\application\ports\BookSearcherInterface;
use app\presentation\books\dto\BookListViewModel;
use app\presentation\books\dto\BookViewModel;
use app\presentation\books\forms\BookFilterForm;
use app\presentation\books\mappers\BookViewModelMapper;
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\common\exceptions\UnexpectedDtoTypeException;
use yii\data\DataProviderInterface;
use yii\web\Request;

final readonly class BookListViewFactory
{
    private const int DEFAULT_LIMIT = 20;

    public function __construct(
        private BookSearcherInterface $searcher,
        private PagedResultDataProviderFactory $dataProviderFactory,
        private BookDtoUrlResolver $urlResolver,
        private BookViewModelMapper $viewModelMapper,
        private BookSearchSpecificationFactory $specificationFactory,
    ) {
    }

    public function getListViewModel(Request $request): BookListViewModel
    {
        $pagination = new PaginationRequest(
            $request->get('page'),
            $request->get('limit'),
            self::DEFAULT_LIMIT,
        );

        $filterForm = new BookFilterForm();
        $filterForm->load((array)$request->get());
        $filterForm->validate();

        return new BookListViewModel(
            $this->getIndexDataProvider($filterForm, $pagination),
            $filterForm,
        );
    }

    private function getIndexDataProvider(
        BookFilterForm $filterForm,
        PaginationRequest $pagination,
    ): DataProviderInterface {
        $yearValue = $filterForm->year !== null && $filterForm->year !== ''
        ? (int)$filterForm->year
        : null;

        $specification = $this->specificationFactory->createFromColumnFilters(
            $filterForm->title !== '' ? $filterForm->title : null,
            $yearValue,
            $filterForm->isbn !== '' ? $filterForm->isbn : null,
            $filterForm->status !== '' ? $filterForm->status : null,
            $filterForm->author !== '' ? $filterForm->author : null,
        );

        $queryResult = $this->searcher->searchBySpecification(
            $specification,
            $pagination->page,
            $pagination->limit,
        );

        $dtos = array_map(
            fn(mixed $dto): BookViewModel => $dto instanceof BookReadDto
                ? $this->viewModelMapper->map($this->urlResolver->resolveUrl($dto))
                : throw new UnexpectedDtoTypeException(BookReadDto::class, $dto),
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
