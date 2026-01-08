<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\queries\BookQueryService;
use app\application\common\dto\PaginationRequest;
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
    private BookQueryService&MockObject $bookQueryService;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private FileUrlResolver&MockObject $fileUrlResolver;
    private BookSearchHandler $handler;

    protected function _before(): void
    {
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->bookQueryService = $this->createMock(BookQueryService::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);
        $this->fileUrlResolver = $this->createMock(FileUrlResolver::class);

        $this->handler = new BookSearchHandler(
            $this->autoMapper,
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
        $this->assertTrue($result['searchModel']->hasErrors());
    }
}
