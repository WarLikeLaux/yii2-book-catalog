<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\authors\queries\AuthorQueryService;
use app\presentation\mappers\AuthorSearchCriteriaMapper;
use app\presentation\mappers\AuthorSelect2Mapper;
use yii\web\Request;
use yii\web\Response;

final readonly class AuthorSearchPresentationService
{
    public function __construct(
        private AuthorSearchCriteriaMapper $authorSearchCriteriaMapper,
        private AuthorSelect2Mapper $authorSelect2Mapper,
        private AuthorQueryService $authorQueryService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function search(Request $request, Response $response): array
    {
        $response->format = Response::FORMAT_JSON;
        /** @var array<string, mixed> $requestParams */
        $requestParams = (array)$request->get();
        $form = $this->authorSearchCriteriaMapper->toForm($requestParams);
        if (!$form->validate()) {
            return $this->authorSelect2Mapper->emptyResult();
        }

        $criteria = $this->authorSearchCriteriaMapper->toCriteria($form);
        $responseData = $this->authorQueryService->search($criteria);

        return $this->authorSelect2Mapper->mapToSelect2($responseData);
    }
}
