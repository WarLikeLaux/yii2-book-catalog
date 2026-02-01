<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookSearcherInterface;
use app\presentation\books\dto\BookListViewModel;
use app\presentation\books\dto\BookViewModel;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;
use LogicException;
use yii\data\DataProviderInterface;

final readonly class BookListViewFactory
{
    public function __construct(
        private BookSearcherInterface $searcher,
        private PagedResultDataProviderFactory $dataProviderFactory,
        private FileUrlResolver $resolver,
    ) {
    }

    public function getListViewModel(int $page, int $pageSize): BookListViewModel
    {
        return new BookListViewModel(
            $this->getIndexDataProvider($page, $pageSize),
        );
    }

    private function getIndexDataProvider(int $page, int $pageSize): DataProviderInterface
    {
        $queryResult = $this->searcher->search('', $page, $pageSize);

        $dtos = array_map(
            fn(mixed $dto): BookViewModel => $dto instanceof BookReadDto
                ? $this->mapToViewModel($this->withResolvedUrl($dto))
                : throw new LogicException('Expected BookReadDto'),
            $queryResult->getModels(),
        );

        $newResult = new QueryResult(
            $dtos,
            $queryResult->getTotalCount(),
            $queryResult->getPagination(),
        );

        return $this->dataProviderFactory->create($newResult);
    }

    private function withResolvedUrl(BookReadDto $dto): BookReadDto
    {
        return $dto->withCoverUrl($this->resolver->resolve($dto->coverUrl));
    }

    private function mapToViewModel(BookReadDto $dto): BookViewModel
    {
        return new BookViewModel(
            $dto->id,
            $dto->title,
            $dto->year,
            $dto->description,
            $dto->isbn,
            $dto->authorNames,
            $dto->coverUrl,
            $dto->isPublished,
        );
    }
}
