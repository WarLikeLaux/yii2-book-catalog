<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\authors\dto\AuthorListViewModel;
use app\presentation\authors\handlers\AuthorListViewFactory;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\data\DataProviderInterface;

final class AuthorListViewFactoryTest extends Unit
{
    private AuthorQueryServiceInterface&MockObject $queryService;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private AuthorListViewFactory $factory;

    protected function _before(): void
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
        $pagedResult = $this->createMock(PagedResultInterface::class);
        $dataProvider = $this->createMock(DataProviderInterface::class);

        $this->queryService->expects($this->once())
            ->method('search')
            ->with('', 1, 20)
            ->willReturn($pagedResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->with($pagedResult)
            ->willReturn($dataProvider);

        $result = $this->factory->getListViewModel(1, 20);

        $this->assertInstanceOf(AuthorListViewModel::class, $result);
        $this->assertSame($dataProvider, $result->dataProvider);
    }
}
