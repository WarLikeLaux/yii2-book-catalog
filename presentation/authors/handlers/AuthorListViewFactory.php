<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\dto\AuthorListViewModel;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use yii\data\DataProviderInterface;

final readonly class AuthorListViewFactory
{
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
        private PagedResultDataProviderFactory $dataProviderFactory,
    ) {
    }

    public function getListViewModel(int $page, int $pageSize): AuthorListViewModel
    {
        return new AuthorListViewModel(
            $this->getIndexDataProvider($page, $pageSize),
        );
    }

    private function getIndexDataProvider(int $page, int $pageSize): DataProviderInterface
    {
        $queryResult = $this->queryService->search('', $page, $pageSize);
        return $this->dataProviderFactory->create($queryResult);
    }
}
