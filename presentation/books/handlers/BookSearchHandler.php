<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookQueryService;
use app\application\books\queries\BookReadDto;
use app\application\books\queries\BookSearchCriteria;
use app\application\common\dto\PaginationRequest;
use app\application\common\dto\QueryResult;
use app\presentation\books\forms\BookSearchForm;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;
use AutoMapper\AutoMapperInterface;

final readonly class BookSearchHandler
{
    public function __construct(
        private AutoMapperInterface $autoMapper,
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
        $form = new BookSearchForm();
        $form->load($params);

        /** @var BookSearchCriteria $criteria */
        $criteria = $this->autoMapper->map(
            $form->toArray() + ['page' => $pagination->page, 'pageSize' => $pagination->limit],
            BookSearchCriteria::class,
        );

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
