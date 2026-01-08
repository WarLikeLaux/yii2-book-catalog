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
    /**
     * Create a new BookSearchHandler with its required dependencies.
     *
     * @param AutoMapperInterface $autoMapper Maps form data to domain/search DTOs.
     * @param BookQueryService $bookQueryService Executes book search queries.
     * @param PagedResultDataProviderFactory $dataProviderFactory Creates paged data providers from query results.
     * @param FileUrlResolver $fileUrlResolver Resolves file (cover) URLs for book DTOs.
     */
    public function __construct(
        private AutoMapperInterface $autoMapper,
        private BookQueryService $bookQueryService,
        private PagedResultDataProviderFactory $dataProviderFactory,
        private FileUrlResolver $fileUrlResolver,
    ) {
    }

    /**
     * Prepare data required by the books index view from raw request parameters and pagination.
     *
     * @param array<string,mixed> $params Raw input parameters to populate the BookSearchForm.
     * @param PaginationRequest $pagination Pagination parameters used for search and empty-result construction when the form is invalid.
     * @return array<string,mixed> Associative array with:
     *   - `searchModel`: the populated BookSearchForm instance,
     *   - `dataProvider`: a paged data provider containing BookReadDto items with resolved cover URLs; if the form is invalid, the provider contains an empty result for the requested page and page size.
     */
    public function prepareIndexViewData(array $params, PaginationRequest $pagination): array
    {
        $form = new BookSearchForm();
        $form->load($params);

        if (!$form->validate()) {
            $emptyResult = QueryResult::empty($pagination->page, $pagination->limit);

            return [
                'searchModel' => $form,
                'dataProvider' => $this->dataProviderFactory->create($emptyResult),
            ];
        }

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