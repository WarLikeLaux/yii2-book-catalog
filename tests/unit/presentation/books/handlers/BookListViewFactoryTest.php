<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\factories\BookSearchSpecificationFactory;
use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookSearcherInterface;
use app\domain\specifications\BookSpecificationInterface;
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
    private FileUrlResolver $resolver;
    private BookDtoUrlResolver $urlResolver;
    private BookViewModelMapper $viewModelMapper;
    private BookSearchSpecificationFactory $specificationFactory;
    private BookListViewFactory $factory;

    protected function setUp(): void
    {
        $this->searcher = $this->createMock(BookSearcherInterface::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);
        $this->resolver = new FileUrlResolver('/uploads');
        $this->urlResolver = new BookDtoUrlResolver($this->resolver);
        $this->viewModelMapper = new BookViewModelMapper();
        $this->specificationFactory = new BookSearchSpecificationFactory();

        $this->factory = new BookListViewFactory(
            $this->searcher,
            $this->dataProviderFactory,
            $this->urlResolver,
            $this->viewModelMapper,
            $this->specificationFactory,
        );
    }

    public function testGetListViewModelReturnsModel(): void
    {
        $dto = new BookReadDto(1, 'T', 2020, null, 'ISBN', [], [], null, BookStatus::Draft->value, 1);
        $queryResult = new QueryResult([$dto], 1, new PaginationDto(1, 20, 1, 1));
        $dataProvider = $this->createStub(DataProviderInterface::class);

        $this->searcher->expects($this->once())
            ->method('searchBySpecification')
            ->with($this->isInstanceOf(BookSpecificationInterface::class), 1, 20)
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
            ->method('searchBySpecification')
            ->with($this->isInstanceOf(BookSpecificationInterface::class), 1, 20)
            ->willReturn($queryResult);

        $factory = new BookListViewFactory(
            $this->searcher,
            $this->createStub(PagedResultDataProviderFactory::class),
            $this->urlResolver,
            $this->viewModelMapper,
            $this->specificationFactory,
        );

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
        ]);

        $this->expectException(UnexpectedDtoTypeException::class);
        $this->expectExceptionMessage(BookReadDto::class);
        $this->expectExceptionMessage('stdClass');

        $factory->getListViewModel($request);
    }

    public function testGetListViewModelWithTitleFilterPassesToSpecification(): void
    {
        $dto = new BookReadDto(1, 'Clean Code', 2008, null, '978', [], [], null, BookStatus::Published->value, 1);
        $queryResult = new QueryResult([$dto], 1, new PaginationDto(1, 20, 1, 1));
        $dataProvider = $this->createStub(DataProviderInterface::class);

        $this->searcher->expects($this->once())
            ->method('searchBySpecification')
            ->with($this->isInstanceOf(BookSpecificationInterface::class), 1, 20)
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

        $this->assertInstanceOf(BookListViewModel::class, $result);
        $this->assertSame('Clean', $result->filterModel->title);
    }

    public function testGetListViewModelWithYearFilterPassesToSpecification(): void
    {
        $dto = new BookReadDto(1, 'T', 2024, null, 'ISBN', [], [], null, BookStatus::Draft->value, 1);
        $queryResult = new QueryResult([$dto], 1, new PaginationDto(1, 20, 1, 1));
        $dataProvider = $this->createStub(DataProviderInterface::class);

        $this->searcher->expects($this->once())
            ->method('searchBySpecification')
            ->with($this->isInstanceOf(BookSpecificationInterface::class), 1, 20)
            ->willReturn($queryResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn($dataProvider);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
            [null, null, null, ['year' => '2024']],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertSame('2024', $result->filterModel->year);
    }
}
