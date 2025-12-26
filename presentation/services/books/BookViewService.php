<?php

declare(strict_types=1);

namespace app\presentation\services\books;

use app\application\authors\queries\AuthorQueryService;
use app\application\books\queries\BookQueryService;
use app\application\books\queries\BookReadDto;
use app\presentation\adapters\PagedResultDataProviderFactory;
use app\presentation\forms\BookForm;
use app\presentation\mappers\BookFormMapper;
use yii\data\DataProviderInterface;

final class BookViewService
{
    public function __construct(
        private readonly BookQueryService $bookQueryService,
        private readonly AuthorQueryService $authorQueryService,
        private readonly BookFormMapper $mapper,
        private readonly PagedResultDataProviderFactory $dataProviderFactory
    ) {
    }

    public function getIndexDataProvider(int $page, int $pageSize): DataProviderInterface
    {
        $queryResult = $this->bookQueryService->getIndexProvider($page, $pageSize);
        return $this->dataProviderFactory->create($queryResult);
    }

    public function getBookForUpdate(int $id): BookForm
    {
        $dto = $this->bookQueryService->getById($id);
        return $this->mapper->toForm($dto);
    }

    public function getBookView(int $id): BookReadDto
    {
        return $this->bookQueryService->getById($id);
    }

    public function getAuthorsList(): array
    {
        return $this->authorQueryService->getAuthorsMap();
    }
}
