<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookSearcherInterface;
use app\presentation\books\dto\BookListViewModel;
use app\presentation\books\handlers\BookListViewFactory;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\data\DataProviderInterface;

final class BookListViewFactoryTest extends Unit
{
    private BookSearcherInterface&MockObject $searcher;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private FileUrlResolver $resolver;
    private BookListViewFactory $factory;

    protected function _before(): void
    {
        $this->searcher = $this->createMock(BookSearcherInterface::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);
        $this->resolver = new FileUrlResolver('/uploads');

        $this->factory = new BookListViewFactory(
            $this->searcher,
            $this->dataProviderFactory,
            $this->resolver,
        );
    }

    public function testGetListViewModelReturnsModel(): void
    {
        $dto = new BookReadDto(1, 'T', 2020, null, 'ISBN', [], [], null, false, 1);
        $queryResult = new QueryResult([$dto], 1, new PaginationDto(1, 20, 1, 1));
        $dataProvider = $this->createMock(DataProviderInterface::class);

        $this->searcher->expects($this->once())
            ->method('search')
            ->with('', 1, 20)
            ->willReturn($queryResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn($dataProvider);

        $result = $this->factory->getListViewModel(1, 20);

        $this->assertInstanceOf(BookListViewModel::class, $result);
        $this->assertSame($dataProvider, $result->dataProvider);
    }
}
