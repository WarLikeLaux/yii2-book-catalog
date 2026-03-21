<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookColumnFilterDto;
use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationRequest;
use app\application\common\dto\QueryResult;
use app\application\common\dto\SortRequest;
use app\application\ports\BookSearcherInterface;
use app\presentation\books\dto\BookListViewModel;
use app\presentation\books\forms\BookFilterForm;
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\common\adapters\PagedResultDataProvider;
use app\presentation\common\exceptions\UnexpectedDtoTypeException;
use yii\data\DataProviderInterface;
use yii\web\Request;

final readonly class BookListViewFactory
{
    private const int DEFAULT_LIMIT = 20;
    private const array SORT_ATTRIBUTES = ['id', 'title', 'year', 'isbn', 'status'];

    public function __construct(
        private BookSearcherInterface $searcher,
        private BookDtoUrlResolver $urlResolver,
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

        /** @var ?string $sortParam */
        $sortParam = $request->get('sort');
        $sort = SortRequest::fromRequest(is_string($sortParam) ? $sortParam : null);

        return new BookListViewModel(
            $this->getIndexDataProvider($filterForm, $pagination, $sort),
            $filterForm,
        );
    }

    private function getIndexDataProvider(
        BookFilterForm $filterForm,
        PaginationRequest $pagination,
        ?SortRequest $sort,
    ): DataProviderInterface {
        $filter = $this->buildFilterDto($filterForm);

        $queryResult = $this->searcher->searchWithFilters(
            $filter,
            $pagination->page,
            $pagination->limit,
            $sort,
        );

        $dtos = array_map(
            fn(mixed $dto): BookReadDto => $dto instanceof BookReadDto
                ? $this->urlResolver->resolveUrl($dto)
                : throw new UnexpectedDtoTypeException(BookReadDto::class, $dto),
            $queryResult->getModels(),
        );

        $newResult = new QueryResult(
            $dtos,
            $queryResult->getTotalCount(),
            $queryResult->getPagination(),
        );

        return new PagedResultDataProvider($newResult, self::SORT_ATTRIBUTES);
    }

    private function buildFilterDto(BookFilterForm $form): BookColumnFilterDto
    {
        return new BookColumnFilterDto(
            id: $form->id !== null && $form->id !== '' ? (int)$form->id : null,
            title: $form->title !== '' ? $form->title : null,
            year: $form->year !== null && $form->year !== '' ? (int)$form->year : null,
            isbn: $form->isbn !== '' ? $form->isbn : null,
            status: $form->status !== '' ? $form->status : null,
            author: $form->author !== '' ? $form->author : null,
        );
    }
}
