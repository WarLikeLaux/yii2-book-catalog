<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\queries\AuthorQueryService;
use app\application\authors\queries\AuthorReadDto;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\mappers\AuthorFormMapper;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use yii\data\DataProviderInterface;

final readonly class AuthorViewDataFactory
{
    public function __construct(
        private AuthorQueryService $authorQueryService,
        private AuthorFormMapper $mapper,
        private PagedResultDataProviderFactory $dataProviderFactory
    ) {
    }

    public function getIndexDataProvider(int $page, int $pageSize): DataProviderInterface
    {
        $queryResult = $this->authorQueryService->getIndexProvider($page, $pageSize);
        return $this->dataProviderFactory->create($queryResult);
    }

    public function getAuthorForUpdate(int $id): AuthorForm
    {
        $dto = $this->authorQueryService->getById($id);
        return $this->mapper->toForm($dto);
    }

    public function getAuthorView(int $id): AuthorReadDto
    {
        return $this->authorQueryService->getById($id);
    }
}
