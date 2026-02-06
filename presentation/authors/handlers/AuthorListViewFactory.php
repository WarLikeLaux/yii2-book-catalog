<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\common\dto\PaginationRequest;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\dto\AuthorListViewModel;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\common\dto\CrudPaginationRequest;
use yii\data\DataProviderInterface;
use yii\web\Request;

final readonly class AuthorListViewFactory
{
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
        private PagedResultDataProviderFactory $dataProviderFactory,
    ) {
    }

    public function getListViewModel(Request $request): AuthorListViewModel
    {
        $pagination = CrudPaginationRequest::fromRequest($request);

        return new AuthorListViewModel(
            $this->getIndexDataProvider($pagination),
        );
    }

    private function getIndexDataProvider(PaginationRequest $pagination): DataProviderInterface
    {
        $queryResult = $this->queryService->search('', $pagination->page, $pagination->limit);
        return $this->dataProviderFactory->create($queryResult);
    }
}
