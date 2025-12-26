<?php

declare(strict_types=1);

namespace app\presentation\services\authors;

use app\application\authors\queries\AuthorQueryService;
use app\application\authors\queries\AuthorReadDto;
use app\presentation\adapters\PagedResultDataProviderFactory;
use app\presentation\forms\AuthorForm;
use app\presentation\mappers\AuthorFormMapper;
use yii\data\DataProviderInterface;

final class AuthorViewService
{
    public function __construct(
        private readonly AuthorQueryService $authorQueryService,
        private readonly AuthorFormMapper $mapper,
        private readonly PagedResultDataProviderFactory $dataProviderFactory
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
