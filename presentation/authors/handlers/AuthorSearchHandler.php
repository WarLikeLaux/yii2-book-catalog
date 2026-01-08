<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\authors\queries\AuthorSearchCriteria;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\forms\AuthorSearchForm;
use AutoMapper\AutoMapperInterface;

final readonly class AuthorSearchHandler
{
    /**
     * Initialize the handler with its required services.
     *
     * @param AuthorQueryServiceInterface $queryService Service used to perform author searches.
     * @param AutoMapperInterface $autoMapper Service used to map form data to search criteria objects.
     */
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
        private AutoMapperInterface $autoMapper,
    ) {
    }

    /**
     * Handle an author search request and return results formatted for a Select2-like response.
     *
     * @param array<string,mixed> $queryParams Raw query parameters used to populate AuthorSearchForm.
     * @return array<string,mixed> {
     *     Response array with the following keys:
     *     @type array<int,array<string,mixed>> $results List of items where each item contains:
     *           - `id` (scalar): Author identifier.
     *           - `text` (string): Author display name (`fio`).
     *     @type array<string,bool> $pagination Pagination info:
     *           - `more` `true` if additional pages exist, `false` otherwise.
     * }
     */
    public function search(array $queryParams): array
    {
        $form = new AuthorSearchForm();
        $form->load($queryParams);

        if (!$form->validate()) {
            return $this->createEmptySelect2Result();
        }

        /** @var AuthorSearchCriteria $criteria */
        $criteria = $this->autoMapper->map($form, AuthorSearchCriteria::class);

        $result = $this->queryService->search(
            $criteria->search,
            $criteria->page,
            $criteria->pageSize,
        );

        /** @var \app\application\authors\queries\AuthorReadDto[] $models */
        $models = $result->getModels();

        return [
            'results' => array_map(static fn(AuthorReadDto $dto): array => [
                'id' => $dto->id,
                'text' => $dto->fio,
            ], $models),
            'pagination' => [
                'more' => $criteria->page * $criteria->pageSize < $result->getTotalCount(),
            ],
        ];
    }

    /**
     * Create an empty response formatted for a Select2-compatible component.
     *
     * @return array{results: list, pagination: array{more: bool}} Associative array with 'results' as an empty list and 'pagination' containing ['more' => false].
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