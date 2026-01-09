<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use AutoMapper\AutoMapperInterface;
use yii\data\DataProviderInterface;
use yii\web\NotFoundHttpException;

final readonly class AuthorViewDataFactory
{
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
        private AutoMapperInterface $autoMapper,
        private PagedResultDataProviderFactory $dataProviderFactory,
    ) {
    }

    public function getIndexDataProvider(int $page, int $pageSize): DataProviderInterface
    {
        $queryResult = $this->queryService->search('', $page, $pageSize);
        return $this->dataProviderFactory->create($queryResult);
    }

    public function getAuthorForUpdate(int $id): AuthorForm
    {
        $dto = $this->queryService->findById($id) ?? throw new NotFoundHttpException();

        /** @var AuthorForm */
        return $this->autoMapper->map($dto, AuthorForm::class);
    }

    public function getAuthorView(int $id): AuthorReadDto
    {
        return $this->queryService->findById($id)
        ?? throw new NotFoundHttpException();
    }
}
