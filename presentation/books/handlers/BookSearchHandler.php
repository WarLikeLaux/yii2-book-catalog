<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookQueryService;
use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationRequest;
use app\application\common\dto\QueryResult;
use app\presentation\books\mappers\BookSearchCriteriaMapper;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;

final readonly class BookSearchHandler
{
    public function __construct(
        private BookSearchCriteriaMapper $bookSearchCriteriaMapper,
        private BookQueryService $bookQueryService,
        private PagedResultDataProviderFactory $dataProviderFactory,
        private FileUrlResolver $fileUrlResolver,
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

        $resolvedItems = [];

        foreach ($result->getModels() as $model) {
            if (!($model instanceof BookReadDto)) {
                continue; // @codeCoverageIgnore
            }

            $resolvedItems[] = $model->withResolvedCoverUrl(
                $this->fileUrlResolver->resolveCoverUrl($model->coverUrl, $model->id),
            );
        }

        $resolvedResult = new QueryResult(
            $resolvedItems,
            $result->getTotalCount(),
            $result->getPagination(),
        );

        $dataProvider = $this->dataProviderFactory->create($resolvedResult);

        return [
            'searchModel' => $form,
            'dataProvider' => $dataProvider,
        ];
    }
}
