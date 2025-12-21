<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\books\queries\BookQueryService;
use app\presentation\adapters\PagedResultDataProviderFactory;
use app\presentation\mappers\BookSearchCriteriaMapper;
use yii\web\Request;

final class BookSearchPresentationService
{
    public function __construct(
        private readonly BookSearchCriteriaMapper $bookSearchCriteriaMapper,
        private readonly BookQueryService $bookQueryService,
        private readonly PagedResultDataProviderFactory $dataProviderFactory
    ) {
    }

    public function prepareIndexViewData(Request $request): array
    {
        $form = $this->bookSearchCriteriaMapper->toForm($request->get());
        $criteria = $this->bookSearchCriteriaMapper->toCriteria($form);
        $result = $this->bookQueryService->search($criteria);
        $dataProvider = $this->dataProviderFactory->create($result);

        return [
            'searchModel' => $form,
            'dataProvider' => $dataProvider,
        ];
    }
}
