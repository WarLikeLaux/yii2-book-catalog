<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\authors\queries\AuthorSearchCriteria;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\forms\AuthorSearchForm;

final readonly class AuthorSearchViewFactory
{
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
    ) {
    }

    /**
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    public function search(array $queryParams): array
    {
        $form = new AuthorSearchForm();
        $form->load($queryParams);

        if (!$form->validate()) {
            return $this->createEmptySelect2Result();
        }

        $criteria = new AuthorSearchCriteria(
            search: $form->q,
            page: $form->page,
            limit: $form->limit,
        );

        $result = $this->queryService->search(
            $criteria->search,
            $criteria->page,
            $criteria->limit,
        );

        /** @var AuthorReadDto[] $models */
        $models = $result->getModels();

        return [
            'results' => array_map(static fn(AuthorReadDto $dto): array => [
                'id' => $dto->id,
                'text' => $dto->fio,
            ], $models),
            'pagination' => [
                'more' => $criteria->page * $criteria->limit < $result->getTotalCount(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createEmptySelect2Result(): array
    {
        return [
            'results' => [],
            'pagination' => [
                'more' => false,
            ],
        ];
    }
}
