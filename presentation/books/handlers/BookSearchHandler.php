<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookQueryService;
use app\application\common\dto\PaginationRequest;
use app\presentation\books\mappers\BookSearchCriteriaMapper;
use app\presentation\common\adapters\PagedResultDataProviderFactory;

final readonly class BookSearchHandler
{
    public function __construct(
        private BookSearchCriteriaMapper $bookSearchCriteriaMapper,
        private BookQueryService $bookQueryService,
        private PagedResultDataProviderFactory $dataProviderFactory
    ) {
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function prepareIndexViewData(array $params, PaginationRequest $pagination): array
    {
        $form = $this->bookSearchCriteriaMapper->toForm($params);
        $criteria = $this->bookSearchCriteriaMapper->toCriteria($form, $pagination->page, $pagination->limit);
        $result = $this->bookQueryService->search($criteria);
        $dataProvider = $this->dataProviderFactory->create($result);

        return [
            'searchModel' => $form,
            'dataProvider' => $dataProvider,
        ];
    }
}
