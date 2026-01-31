<?php

declare(strict_types=1);

namespace app\presentation\controllers\api\v1;

use app\presentation\books\handlers\BookListViewFactory;
use app\presentation\common\dto\CrudPaginationRequest;
use app\presentation\common\filters\IdempotencyFilter;
use app\presentation\common\filters\RateLimitFilter;
use OpenApi\Attributes as OA;
use Override;
use yii\data\DataProviderInterface;

#[OA\Tag(name: 'API Books', description: 'REST API для работы с книгами')]
final class BookController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        private readonly BookListViewFactory $listViewFactory,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /** @return array<int|string, mixed> */
    #[Override]
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['rateLimit'] = [
            'class' => RateLimitFilter::class,
        ];

        $behaviors['idempotency'] = [
            'class' => IdempotencyFilter::class,
        ];

        return $behaviors;
    }

    #[OA\Get(
        path: '/api/v1/books',
        summary: 'Получить список книг (JSON)',
        tags: ['API Books'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'pageSize', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'JSON список книг',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Book'),
                        ),
                        new OA\Property(property: '_meta', ref: '#/components/schemas/PaginationMeta'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function actionIndex(): DataProviderInterface
    {
        $pagination = CrudPaginationRequest::fromRequest($this->request);

        return $this->listViewFactory->getListViewModel(
            $pagination->page,
            $pagination->limit,
        )->dataProvider;
    }
}
