<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\common\dto\SortDirection;
use app\application\common\dto\SortRequest;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\authors\dto\AuthorListViewModel;
use app\presentation\authors\forms\AuthorFilterForm;
use app\presentation\authors\handlers\AuthorListViewFactory;
use app\presentation\common\adapters\PagedResultDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\web\Request;

final class AuthorListViewFactoryTest extends TestCase
{
    private AuthorQueryServiceInterface&MockObject $queryService;
    private AuthorListViewFactory $factory;

    protected function setUp(): void
    {
        $this->queryService = $this->createMock(AuthorQueryServiceInterface::class);

        $this->factory = new AuthorListViewFactory(
            $this->queryService,
        );
    }

    public function testGetListViewModelReturnsModel(): void
    {
        $pagedResult = $this->createStub(PagedResultInterface::class);

        $this->queryService->expects($this->once())
            ->method('searchWithFilters')
            ->with(null, '', 1, 20, null)
            ->willReturn($pagedResult);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, 1],
            ['limit', null, 20],
            ['sort', null, null],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertInstanceOf(AuthorListViewModel::class, $result);
        $this->assertInstanceOf(PagedResultDataProvider::class, $result->dataProvider);
        $this->assertInstanceOf(AuthorFilterForm::class, $result->filterModel);
    }

    public function testGetListViewModelWithFioFilterPassesToSearch(): void
    {
        $pagedResult = $this->createStub(PagedResultInterface::class);

        $this->queryService->expects($this->once())
            ->method('searchWithFilters')
            ->with(null, 'Пушкин', 1, 20, null)
            ->willReturn($pagedResult);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, 1],
            ['limit', null, 20],
            ['sort', null, null],
            [null, null, ['fio' => 'Пушкин']],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertSame('Пушкин', $result->filterModel->fio);
    }

    public function testGetListViewModelWithIdFilterPassesToSearch(): void
    {
        $pagedResult = $this->createStub(PagedResultInterface::class);

        $this->queryService->expects($this->once())
            ->method('searchWithFilters')
            ->with(5, '', 1, 20, null)
            ->willReturn($pagedResult);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, 1],
            ['limit', null, 20],
            ['sort', null, null],
            [null, null, ['id' => '5']],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertSame('5', $result->filterModel->id);
    }

    public function testGetListViewModelWithSortPassesSortRequest(): void
    {
        $pagedResult = $this->createStub(PagedResultInterface::class);

        $this->queryService->expects($this->once())
            ->method('searchWithFilters')
            ->with(
                null,
                '',
                1,
                20,
                $this->callback(
                    static fn(?SortRequest $s): bool => $s instanceof SortRequest
                        && $s->field === 'fio'
                        && $s->direction === SortDirection::ASC,
                ),
            )
            ->willReturn($pagedResult);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, 1],
            ['limit', null, 20],
            ['sort', null, 'fio'],
        ]);

        $this->factory->getListViewModel($request);
    }
}
