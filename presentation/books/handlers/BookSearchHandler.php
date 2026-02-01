<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationRequest;
use app\application\common\dto\QueryResult;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\dto\BookIndexViewModel;
use app\presentation\books\forms\BookSearchForm;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;

final readonly class BookSearchHandler
{
    public function __construct(
        private BookQueryServiceInterface $bookQueryService,
        private PagedResultDataProviderFactory $dataProviderFactory,
        private FileUrlResolver $fileUrlResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function prepareIndexViewModel(array $params, PaginationRequest $pagination): BookIndexViewModel
    {
        $form = new BookSearchForm();
        $form->load($params);

        if (!$form->validate()) {
            $emptyResult = QueryResult::empty($pagination->page, $pagination->limit);

            return new BookIndexViewModel(
                $form,
                $this->dataProviderFactory->create($emptyResult),
            );
        }

        $result = $this->bookQueryService->search(
            $form->globalSearch,
            $pagination->page,
            $pagination->limit,
        );

        $resolvedItems = [];

        foreach ($result->getModels() as $model) {
            if (!($model instanceof BookReadDto)) {
                continue; // @codeCoverageIgnore
            }

            $resolvedItems[] = $model->withCoverUrl(
                $this->fileUrlResolver->resolveCoverUrl($model->coverUrl, $model->id),
            );
        }

        $resolvedResult = new QueryResult(
            $resolvedItems,
            $result->getTotalCount(),
            $result->getPagination(),
        );

        $dataProvider = $this->dataProviderFactory->create($resolvedResult);

        return new BookIndexViewModel(
            $form,
            $dataProvider,
        );
    }
}
