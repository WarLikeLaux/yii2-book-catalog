<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\common\dto\PaginationRequest;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\dto\AuthorListViewModel;
use app\presentation\authors\forms\AuthorFilterForm;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use yii\data\DataProviderInterface;
use yii\web\Request;

final readonly class AuthorListViewFactory
{
    private const int DEFAULT_LIMIT = 20;

    public function __construct(
        private AuthorQueryServiceInterface $queryService,
        private PagedResultDataProviderFactory $dataProviderFactory,
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

        return new AuthorListViewModel(
            $this->getIndexDataProvider($filterForm, $pagination),
            $filterForm,
        );
    }

    private function getIndexDataProvider(
        AuthorFilterForm $filterForm,
        PaginationRequest $pagination,
    ): DataProviderInterface {
        $idValue = $filterForm->id !== null && $filterForm->id !== ''
        ? (int)$filterForm->id
        : null;

        $queryResult = $this->queryService->searchWithFilters(
            $idValue,
            $filterForm->fio,
            $pagination->page,
            $pagination->limit,
        );

        return $this->dataProviderFactory->create($queryResult);
    }
}
