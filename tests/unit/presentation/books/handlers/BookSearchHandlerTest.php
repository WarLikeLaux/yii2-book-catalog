<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\PaginationRequest;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\books\forms\BookSearchForm;
use app\presentation\books\handlers\BookSearchHandler;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\data\ArrayDataProvider;

final class BookSearchHandlerTest extends Unit
{
    private AutoMapperInterface&MockObject $autoMapper;
    private BookQueryServiceInterface&MockObject $bookQueryService;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private FileUrlResolver&MockObject $fileUrlResolver;
    private BookSearchHandler $handler;

    protected function _before(): void
    {
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->bookQueryService = $this->createMock(BookQueryServiceInterface::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);
        $this->fileUrlResolver = $this->createMock(FileUrlResolver::class);

        $this->handler = new BookSearchHandler(
            $this->bookQueryService,
            $this->dataProviderFactory,
            $this->fileUrlResolver,
        );
    }

    public function testPrepareIndexViewDataReturnsEmptyResultOnValidationFailure(): void
    {
        $params = ['globalSearch' => 'x'];
        $pagination = new PaginationRequest(1, 20);

        $this->dataProviderFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new ArrayDataProvider(['allModels' => []]));

        $this->bookQueryService->expects($this->never())->method('search');

        $result = $this->handler->prepareIndexViewData($params, $pagination);

        $this->assertArrayHasKey('searchModel', $result);
        $this->assertArrayHasKey('dataProvider', $result);
        $this->assertInstanceOf(BookSearchForm::class, $result['searchModel']);
        $this->assertInstanceOf(ArrayDataProvider::class, $result['dataProvider']);
        $this->assertTrue($result['searchModel']->hasErrors());
    }

    public function testPrepareIndexViewDataReturnsDataOnSuccess(): void
    {
        $params = ['globalSearch' => 'query'];
        $pagination = new PaginationRequest(1, 20);

        $dto = new BookReadDto(
            1,
            'Test Book',
            2021,
            'Description',
            '978-3-16-148410-0',
            [],
            [],
            'cover.jpg',
        );

        $pagedResult = $this->createMock(PagedResultInterface::class);
        $pagedResult->method('getModels')->willReturn([$dto]);
        $pagedResult->method('getTotalCount')->willReturn(1);
        $pagedResult->method('getPagination')->willReturn(new PaginationDto(1, 20, 1, 1));

        $this->bookQueryService->expects($this->once())
            ->method('search')
            ->with('query', 1, 20)
            ->willReturn($pagedResult);

        $this->fileUrlResolver->expects($this->once())
            ->method('resolveCoverUrl')
            ->with('cover.jpg', 1)
            ->willReturn('resolved.jpg');

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->willReturn(new ArrayDataProvider(['allModels' => [$dto]]));

        $result = $this->handler->prepareIndexViewData($params, $pagination);

        $this->assertArrayHasKey('searchModel', $result);
        $this->assertArrayHasKey('dataProvider', $result);
        $this->assertFalse($result['searchModel']->hasErrors());
    }
}
