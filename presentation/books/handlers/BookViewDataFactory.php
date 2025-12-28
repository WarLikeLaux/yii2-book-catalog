<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\authors\queries\AuthorQueryService;
use app\application\books\queries\BookQueryService;
use app\application\books\queries\BookReadDto;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookFormMapper;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use yii\data\DataProviderInterface;

final readonly class BookViewDataFactory
{
    public function __construct(
        private BookQueryService $bookQueryService,
        private AuthorQueryService $authorQueryService,
        private BookFormMapper $mapper,
        private PagedResultDataProviderFactory $dataProviderFactory
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

    /**
     * @return array<int, string>
     */
    public function getAuthorsList(): array
    {
        return $this->authorQueryService->getAuthorsMap();
    }
}
