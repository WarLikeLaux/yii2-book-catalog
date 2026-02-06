<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\books\dto\BookIndexViewModel;
use app\presentation\books\forms\BookSearchForm;
use app\presentation\books\handlers\BookSearchViewFactory;
use app\presentation\books\mappers\BookViewModelMapper;
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\data\ArrayDataProvider;
use yii\web\Request;

final class BookSearchViewFactoryTest extends Unit
{
    private BookQueryServiceInterface&MockObject $bookQueryService;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private BookDtoUrlResolver&MockObject $urlResolver;
    private BookViewModelMapper $viewModelMapper;
    private BookSearchViewFactory $viewFactory;

    protected function _before(): void
    {
        $this->bookQueryService = $this->createMock(BookQueryServiceInterface::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);
        $this->urlResolver = $this->createMock(BookDtoUrlResolver::class);
        $this->viewModelMapper = new BookViewModelMapper();

        $this->viewFactory = new BookSearchViewFactory(
            $this->bookQueryService,
            $this->dataProviderFactory,
            $this->urlResolver,
            $this->viewModelMapper,
        );
    }

    public function testPrepareIndexViewModelReturnsEmptyResultOnValidationFailure(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('get')->willReturnCallback(
            static fn(?string $name = null, mixed $default = null): mixed => match ($name) {
                null => ['globalSearch' => 'x'],
                'page' => 1,
                'limit' => null,
                default => $default,
            },
        );

        $this->dataProviderFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new ArrayDataProvider(['allModels' => []]));

        $this->bookQueryService->expects($this->never())->method('search');

        $result = $this->viewFactory->prepareIndexViewModel($request);

        $this->assertInstanceOf(BookIndexViewModel::class, $result);
        $this->assertInstanceOf(BookSearchForm::class, $result->searchModel);
        $this->assertInstanceOf(ArrayDataProvider::class, $result->dataProvider);
        $this->assertTrue($result->searchModel->hasErrors());
    }

    public function testPrepareIndexViewModelReturnsDataOnSuccess(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('get')->willReturnCallback(
            static fn(?string $name = null, mixed $default = null): mixed => match ($name) {
                null => ['globalSearch' => 'query'],
                'page' => 1,
                'limit' => 20,
                default => $default,
            },
        );

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

        $resolvedDto = $dto->withCoverUrl('resolved.jpg');
        $this->urlResolver->expects($this->once())
            ->method('resolveUrl')
            ->with($dto)
            ->willReturn($resolvedDto);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (PagedResultInterface $result) use ($dto) {
                $models = $result->getModels();
                return count($models) === 1 && $models[0]->coverUrl === 'resolved.jpg';
            }))
            ->willReturn(new ArrayDataProvider(['allModels' => [$dto]]));

        $result = $this->viewFactory->prepareIndexViewModel($request);

        $this->assertInstanceOf(BookIndexViewModel::class, $result);
        $this->assertFalse($result->searchModel->hasErrors());
    }
}
