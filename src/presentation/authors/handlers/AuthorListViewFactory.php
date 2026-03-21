<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\common\dto\PaginationRequest;
use app\application\common\dto\SortRequest;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\dto\AuthorListViewModel;
use app\presentation\authors\forms\AuthorFilterForm;
use app\presentation\common\adapters\PagedResultDataProvider;
use yii\data\DataProviderInterface;
use yii\web\Request;

final readonly class AuthorListViewFactory
{
    private const int DEFAULT_LIMIT = 20;
    private const array SORT_ATTRIBUTES = ['id', 'fio'];

    public function __construct(
        private AuthorQueryServiceInterface $queryService,
    ) {
    }

    public function getListViewModel(Request $request): AuthorListViewModel
    {
        $pagination = new PaginationRequest(
            $request->get('page'),
            $request->get('limit'),
            self::DEFAULT_LIMIT,
        );

        $filterForm = new AuthorFilterForm();
        $filterForm->load((array)$request->get());
        $filterForm->validate();

        /** @var ?string $sortParam */
        $sortParam = $request->get('sort');
        $sort = SortRequest::fromRequest(is_string($sortParam) ? $sortParam : null);

        return new AuthorListViewModel(
            $this->getIndexDataProvider($filterForm, $pagination, $sort),
            $filterForm,
        );
    }

    private function getIndexDataProvider(
        AuthorFilterForm $filterForm,
        PaginationRequest $pagination,
        ?SortRequest $sort,
    ): DataProviderInterface {
        $idValue = $filterForm->id !== null && $filterForm->id !== ''
        ? (int)$filterForm->id
        : null;

        $queryResult = $this->queryService->searchWithFilters(
            $idValue,
            $filterForm->fio,
            $pagination->page,
            $pagination->limit,
            $sort,
        );

        return new PagedResultDataProvider($queryResult, self::SORT_ATTRIBUTES);
    }
}
