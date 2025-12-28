<?php

declare(strict_types=1);

namespace app\presentation\controllers\api;

use app\application\common\dto\PaginationRequest;
use app\presentation\books\handlers\BookViewDataFactory;
use app\presentation\common\filters\IdempotencyFilter;
use OpenApi\Attributes as OA;
use yii\data\DataProviderInterface;
use yii\filters\AccessControl;
use yii\filters\Cors;
use yii\rest\Controller;

#[OA\Tag(name: 'API Books', description: 'REST API для работы с книгами')]
final class BookController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly BookViewDataFactory $viewDataFactory,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    /** @return array<int|string, mixed> */
    #[\Override]
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['idempotency'] = [
            'class' => IdempotencyFilter::class,
        ];
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
        ];
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'actions' => ['index'],
                    'allow' => true,
                    'roles' => ['?', '@'],
                ],
            ],
        ];

        return $behaviors;
    }

    #[OA\Get(
        path: '/api/books',
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
                            items: new OA\Items(ref: '#/components/schemas/Book')
                        ),
                        new OA\Property(property: '_meta', ref: '#/components/schemas/PaginationMeta'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function actionIndex(): DataProviderInterface
    {
        $pagination = new PaginationRequest(
            $this->request->get('page'),
            $this->request->get('pageSize')
        );

        return $this->viewDataFactory->getIndexDataProvider(
            $pagination->page,
            $pagination->limit
        );
    }
}
