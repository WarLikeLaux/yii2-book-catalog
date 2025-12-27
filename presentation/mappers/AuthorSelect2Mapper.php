<?php

declare(strict_types=1);

namespace app\presentation\mappers;

use app\application\authors\queries\AuthorReadDto;
use app\application\authors\queries\AuthorSearchResponse;

final class AuthorSelect2Mapper
{
    /**
     * @return array<string, mixed>
     */
    public function emptyResult(): array
    {
        return [
            'results' => [],
            'pagination' => [
                'more' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapToSelect2(AuthorSearchResponse $response): array
    {
        return [
            'results' => array_map(fn(AuthorReadDto $dto) => [
                'id' => $dto->id,
                'text' => $dto->fio,
            ], $response->items),
            'pagination' => [
                'more' => $response->page * $response->pageSize < $response->total,
            ],
        ];
    }
}
