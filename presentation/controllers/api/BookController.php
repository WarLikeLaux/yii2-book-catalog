<?php

declare(strict_types=1);

namespace app\presentation\controllers\api;

use app\application\common\dto\PaginationRequest;
use app\presentation\services\books\BookViewService;
use OpenApi\Attributes as OA;
use yii\data\DataProviderInterface;
use yii\filters\AccessControl;
use yii\filters\Cors;
use yii\rest\Controller;

#[OA\Tag(name: 'API Books', description: 'REST API for Books')]
final class BookController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly BookViewService $viewService,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
    }

    #[\Override]
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
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
        summary: 'List all books (JSON)',
        tags: ['API Books'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'pageSize', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'JSON List of books',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: '_meta', type: 'object'),
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

        return $this->viewService->getIndexDataProvider(
            $pagination->page,
            $pagination->limit
        );
    }
}
