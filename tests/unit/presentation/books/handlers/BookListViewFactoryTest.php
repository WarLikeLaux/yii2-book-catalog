<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\queries\BookColumnFilterDto;
use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\common\dto\SortDirection;
use app\application\common\dto\SortRequest;
use app\application\ports\BookSearcherInterface;
use app\domain\values\BookStatus;
use app\presentation\books\dto\BookListViewModel;
use app\presentation\books\forms\BookFilterForm;
use app\presentation\books\handlers\BookListViewFactory;
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\common\adapters\PagedResultDataProvider;
use app\presentation\common\exceptions\UnexpectedDtoTypeException;
use app\presentation\services\FileUrlResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\web\Request;

final class BookListViewFactoryTest extends TestCase
{
    private BookSearcherInterface&MockObject $searcher;
    private BookListViewFactory $factory;

    protected function setUp(): void
    {
        $this->searcher = $this->createMock(BookSearcherInterface::class);
        $resolver = new FileUrlResolver('/uploads');
        $urlResolver = new BookDtoUrlResolver($resolver);

        $this->factory = new BookListViewFactory(
            $this->searcher,
            $urlResolver,
        );
    }

    public function testGetListViewModelReturnsModel(): void
    {
        $dto = new BookReadDto(1, 'T', 2020, null, 'ISBN', [], [], null, BookStatus::Draft->value, 1);
        $queryResult = new QueryResult([$dto], 1, new PaginationDto(1, 20, 1, 1));

        $this->searcher->expects($this->once())
            ->method('searchWithFilters')
            ->with($this->isInstanceOf(BookColumnFilterDto::class), 1, 20, null)
            ->willReturn($queryResult);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
            ['sort', null, null, null],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertInstanceOf(BookListViewModel::class, $result);
        $this->assertInstanceOf(PagedResultDataProvider::class, $result->dataProvider);
        $this->assertInstanceOf(BookFilterForm::class, $result->filterModel);
    }

    public function testGetListViewModelThrowsWhenInvalidDtoType(): void
    {
        $queryResult = new QueryResult([new \stdClass()], 1, new PaginationDto(1, 20, 1, 1));

        $this->searcher->expects($this->once())
            ->method('searchWithFilters')
            ->willReturn($queryResult);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
            ['sort', null, null, null],
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

        $this->searcher->expects($this->once())
            ->method('searchWithFilters')
            ->with(
                $this->callback(static fn(BookColumnFilterDto $f): bool => $f->title === 'Clean' && $f->id === null),
                1,
                20,
                null,
            )
            ->willReturn($queryResult);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
            ['sort', null, null, null],
            [null, null, null, ['title' => 'Clean']],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertSame('Clean', $result->filterModel->title);
    }

    public function testGetListViewModelWithSortPassesSortRequest(): void
    {
        $dto = new BookReadDto(1, 'T', 2020, null, 'ISBN', [], [], null, BookStatus::Draft->value, 1);
        $queryResult = new QueryResult([$dto], 1, new PaginationDto(1, 20, 1, 1));

        $this->searcher->expects($this->once())
            ->method('searchWithFilters')
            ->with(
                $this->isInstanceOf(BookColumnFilterDto::class),
                1,
                20,
                $this->callback(
                    static fn(?SortRequest $s): bool => $s instanceof SortRequest
                        && $s->field === 'title'
                        && $s->direction === SortDirection::DESC,
                ),
            )
            ->willReturn($queryResult);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, null, 1],
            ['limit', null, null, 20],
            ['sort', null, null, '-title'],
        ]);

        $this->factory->getListViewModel($request);
    }
}
