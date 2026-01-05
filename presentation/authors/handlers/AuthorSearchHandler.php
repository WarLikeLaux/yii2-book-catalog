<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\queries\AuthorQueryService;
use app\presentation\authors\mappers\AuthorSearchCriteriaMapper;
use app\presentation\authors\mappers\AuthorSelect2Mapper;
use yii\web\Response;

final readonly class AuthorSearchHandler
{
    public function __construct(
        private AuthorSearchCriteriaMapper $authorSearchCriteriaMapper,
        private AuthorSelect2Mapper $authorSelect2Mapper,
        private AuthorQueryService $authorQueryService,
    ) {
    }

    /**
     * @param array<string, mixed> $queryParams
     * @codeCoverageIgnore Мутирует Yii Response, тестируется функционально
     * @return array<string, mixed>
     */
    public function search(array $queryParams, Response $response): array
    {
        $response->format = Response::FORMAT_JSON;
        $form = $this->authorSearchCriteriaMapper->toForm($queryParams);

        if (!$form->validate()) {
            return $this->authorSelect2Mapper->emptyResult();
        }

        $criteria = $this->authorSearchCriteriaMapper->toCriteria($form);
        $responseData = $this->authorQueryService->search($criteria);

        return $this->authorSelect2Mapper->mapToSelect2($responseData);
    }
}
