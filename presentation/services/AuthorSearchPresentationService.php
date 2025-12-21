<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\authors\queries\AuthorQueryService;
use app\models\forms\AuthorSearchForm;
use app\presentation\mappers\AuthorSearchCriteriaMapper;
use app\presentation\mappers\AuthorSelect2Mapper;

final class AuthorSearchPresentationService
{
    public function __construct(
        private readonly AuthorSearchCriteriaMapper $authorSearchCriteriaMapper,
        private readonly AuthorSelect2Mapper $authorSelect2Mapper,
        private readonly AuthorQueryService $authorQueryService
    ) {
    }

    public function search(array $requestParams): array
    {
        $form = $this->authorSearchCriteriaMapper->toForm($requestParams);
        if (!$form->validate()) {
            return $this->authorSelect2Mapper->emptyResult();
        }

        $criteria = $this->authorSearchCriteriaMapper->toCriteria($form);
        $response = $this->authorQueryService->search($criteria);

        return $this->authorSelect2Mapper->mapToSelect2($response);
    }
}

