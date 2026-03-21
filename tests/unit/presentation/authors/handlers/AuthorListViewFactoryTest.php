<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\authors\dto\AuthorListViewModel;
use app\presentation\authors\forms\AuthorFilterForm;
use app\presentation\authors\handlers\AuthorListViewFactory;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\data\DataProviderInterface;
use yii\web\Request;

final class AuthorListViewFactoryTest extends TestCase
{
    private AuthorQueryServiceInterface&MockObject $queryService;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private AuthorListViewFactory $factory;

    protected function setUp(): void
    {
        $this->queryService = $this->createMock(AuthorQueryServiceInterface::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);

        $this->factory = new AuthorListViewFactory(
            $this->queryService,
            $this->dataProviderFactory,
        );
    }

    public function testGetListViewModelReturnsModel(): void
    {
        $pagedResult = $this->createStub(PagedResultInterface::class);
        $dataProvider = $this->createStub(DataProviderInterface::class);

        $this->queryService->expects($this->once())
            ->method('searchWithFilters')
            ->with(null, '', 1, 20)
            ->willReturn($pagedResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->with($pagedResult)
            ->willReturn($dataProvider);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, 1],
            ['limit', null, 20],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertInstanceOf(AuthorListViewModel::class, $result);
        $this->assertSame($dataProvider, $result->dataProvider);
        $this->assertInstanceOf(AuthorFilterForm::class, $result->filterModel);
    }

    public function testGetListViewModelWithFioFilterPassesToSearch(): void
    {
        $pagedResult = $this->createStub(PagedResultInterface::class);
        $dataProvider = $this->createStub(DataProviderInterface::class);

        $this->queryService->expects($this->once())
            ->method('searchWithFilters')
            ->with(null, 'Пушкин', 1, 20)
            ->willReturn($pagedResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn($dataProvider);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, 1],
            ['limit', null, 20],
            [null, null, ['fio' => 'Пушкин']],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertSame('Пушкин', $result->filterModel->fio);
    }

    public function testGetListViewModelWithIdFilterPassesToSearch(): void
    {
        $pagedResult = $this->createStub(PagedResultInterface::class);
        $dataProvider = $this->createStub(DataProviderInterface::class);

        $this->queryService->expects($this->once())
            ->method('searchWithFilters')
            ->with(5, '', 1, 20)
            ->willReturn($pagedResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn($dataProvider);

        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnMap([
            ['page', null, 1],
            ['limit', null, 20],
            [null, null, ['id' => '5']],
        ]);

        $result = $this->factory->getListViewModel($request);

        $this->assertSame('5', $result->filterModel->id);
    }
}
