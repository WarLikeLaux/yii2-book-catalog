<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\queries\BookColumnFilterDto;
use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookSearcherInterface;
use app\domain\values\BookStatus;
use app\presentation\books\dto\BookListViewModel;
use app\presentation\books\forms\BookFilterForm;
use app\presentation\books\handlers\BookListViewFactory;
use app\presentation\books\mappers\BookViewModelMapper;
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\common\exceptions\UnexpectedDtoTypeException;
use app\presentation\services\FileUrlResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\data\DataProviderInterface;
use yii\web\Request;

final class BookListViewFactoryTest extends TestCase
{
    private BookSearcherInterface&MockObject $searcher;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private BookListViewFactory $factory;

    protected function setUp(): void
    {
        $this->searcher = $this->createMock(BookSearcherInterface::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);
        $resolver = new FileUrlResolver('/uploads');
        $urlResolver = new BookDtoUrlResolver($resolver);
        $viewModelMapper = new BookViewModelMapper();

        $this->factory = new BookListViewFactory(
            $this->searcher,
            $this->dataProviderFactory,
            $urlResolver,
            $viewModelMapper,
        );
    }

    public function testGetListViewModelReturnsModel(): void
    {
        $dto = new BookReadDto(1, 'T', 2020, null, 'ISBN', [], [], null, BookStatus::Draft->value, 1);
        $queryResult = new QueryResult([$dto], 1, new PaginationDto(1, 20, 1, 1));
        $dataProvider = $this->createStub(DataProviderInterface::class);

        $this->searcher->expects($this->once())
            ->method('searchWithFilters')
            ->with($this->isInstanceOf(BookColumnFilterDto::class), 1, 20)
            ->willReturn($queryResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn($dataProvider);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertInstanceOf(BookListViewModel::class, $result);
        $this->assertSame($dataProvider, $result->dataProvider);
        $this->assertInstanceOf(BookFilterForm::class, $result->filterModel);
    }

    public function testGetListViewModelThrowsWhenInvalidDtoType(): void
    {
        $this->dataProviderFactory->expects($this->never())->method($this->anything());
        $queryResult = new QueryResult([new \stdClass()], 1, new PaginationDto(1, 20, 1, 1));

        $this->searcher->expects($this->once())
            ->method('searchWithFilters')
            ->with($this->isInstanceOf(BookColumnFilterDto::class), 1, 20)
            ->willReturn($queryResult);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
        ]);

        $this->expectException(UnexpectedDtoTypeException::class);
        $this->expectExceptionMessage(BookReadDto::class);
        $this->expectExceptionMessage('stdClass');

        $this->factory->getListViewModel($request);
    }

    public function testGetListViewModelWithTitleFilterPassesDto(): void
    {
        $dto = new BookReadDto(1, 'Clean Code', 2008, null, '978', [], [], null, BookStatus::Published->value, 1);
        $queryResult = new QueryResult([$dto], 1, new PaginationDto(1, 20, 1, 1));
        $dataProvider = $this->createStub(DataProviderInterface::class);

        $this->searcher->expects($this->once())
            ->method('searchWithFilters')
            ->with(
                $this->callback(static fn(BookColumnFilterDto $f): bool => $f->title === 'Clean' && $f->id === null),
                1,
                20,
            )
            ->willReturn($queryResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn($dataProvider);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
            [null, null, null, ['title' => 'Clean']],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertSame('Clean', $result->filterModel->title);
    }

    public function testGetListViewModelWithIdAndYearFilters(): void
    {
        $dto = new BookReadDto(42, 'T', 2024, null, 'ISBN', [], [], null, BookStatus::Draft->value, 1);
        $queryResult = new QueryResult([$dto], 1, new PaginationDto(1, 20, 1, 1));
        $dataProvider = $this->createStub(DataProviderInterface::class);

        $this->searcher->expects($this->once())
            ->method('searchWithFilters')
            ->with(
                $this->callback(static fn(BookColumnFilterDto $f): bool => $f->id === 42 && $f->year === 2024),
                1,
                20,
            )
            ->willReturn($queryResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn($dataProvider);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
            [null, null, null, ['id' => '42', 'year' => '2024']],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertSame('42', $result->filterModel->id);
        $this->assertSame('2024', $result->filterModel->year);
    }
}
