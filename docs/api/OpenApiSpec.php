<?php

declare(strict_types=1);

namespace app\docs\api;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Yii2 Book Catalog API', version: '1.0.0', description: 'Modern Yii2 Book Catalog API Documentation')]
#[OA\Server(url: 'http://localhost:8000', description: 'Local Development Server')]
#[OA\Schema(
    schema: 'Book',
    title: 'Book',
    description: 'Книга',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Чистый код'),
        new OA\Property(property: 'year', type: 'integer', nullable: true, example: 2008),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Руководство по созданию качественного кода'),
        new OA\Property(property: 'isbn', type: 'string', example: '978-0-13-235088-4'),
        new OA\Property(property: 'authorIds', type: 'array', items: new OA\Items(type: 'integer'), example: [1]),
        new OA\Property(
            property: 'authorNames',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['1' => 'Роберт Мартин']
        ),
        new OA\Property(property: 'coverUrl', type: 'string', nullable: true, example: '/uploads/covers/clean-code.jpg'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    title: 'Pagination Meta',
    description: 'Метаданные пагинации',
    properties: [
        new OA\Property(property: 'totalCount', type: 'integer', example: 100),
        new OA\Property(property: 'pageCount', type: 'integer', example: 10),
        new OA\Property(property: 'currentPage', type: 'integer', example: 1),
        new OA\Property(property: 'perPage', type: 'integer', example: 10),
    ],
    type: 'object'
)]
class OpenApiSpec
{
}
