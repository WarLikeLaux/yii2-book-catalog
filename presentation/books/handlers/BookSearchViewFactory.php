<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\dto\BookIndexViewModel;
use app\presentation\books\forms\BookSearchForm;
use app\presentation\books\mappers\BookViewModelMapper;
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\common\dto\CatalogPaginationRequest;
use yii\web\Request;

final readonly class BookSearchViewFactory
{
    public function __construct(
        private BookQueryServiceInterface $bookQueryService,
        private PagedResultDataProviderFactory $dataProviderFactory,
        private BookDtoUrlResolver $urlResolver,
        private BookViewModelMapper $viewModelMapper,
    ) {
    }

    public function prepareIndexViewModel(Request $request): BookIndexViewModel
    {
        $pagination = CatalogPaginationRequest::fromRequest($request);
        $form = new BookSearchForm();
        /** @var array<string, mixed> $params */
        $params = (array)$request->get();
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

            $resolvedItems[] = $this->viewModelMapper->map(
                $this->urlResolver->resolveUrl($model),
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
