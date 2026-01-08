<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\queries\AuthorSearchResponse;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\mappers\AuthorSearchCriteriaMapper;
use app\presentation\authors\mappers\AuthorSelect2Mapper;

final readonly class AuthorSearchHandler
{
    public function __construct(
        private AuthorSearchCriteriaMapper $authorSearchCriteriaMapper,
        private AuthorSelect2Mapper $authorSelect2Mapper,
        private AuthorQueryServiceInterface $queryService,
    ) {
    }

    /**
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    public function search(array $queryParams): array
    {
        $form = $this->authorSearchCriteriaMapper->toForm($queryParams);

        if (!$form->validate()) {
            return $this->authorSelect2Mapper->emptyResult();
        }

        $criteria = $this->authorSearchCriteriaMapper->toCriteria($form);
        $result = $this->queryService->search(
            $criteria->search,
            $criteria->page,
            $criteria->pageSize,
        );

        /** @var \app\application\authors\queries\AuthorReadDto[] $items */
        $items = $result->getModels();

        $responseData = new AuthorSearchResponse(
            items: $items,
            total: $result->getTotalCount(),
            page: $criteria->page,
            pageSize: $criteria->pageSize,
        );

        return $this->authorSelect2Mapper->mapToSelect2($responseData);
    }
}
