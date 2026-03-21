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
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\common\adapters\PagedResultDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use yii\web\Request;

final class BookSearchViewFactoryTest extends TestCase
{
    private const ISBN_DEFAULT = '978-3-16-148410-0';
    private BookQueryServiceInterface&MockObject $bookQueryService;
    private BookDtoUrlResolver&MockObject $urlResolver;
    private BookSearchViewFactory $viewFactory;

    protected function setUp(): void
    {
        $this->bookQueryService = $this->createMock(BookQueryServiceInterface::class);
        $this->urlResolver = $this->createMock(BookDtoUrlResolver::class);

        $this->viewFactory = new BookSearchViewFactory(
            $this->bookQueryService,
            $this->urlResolver,
        );
    }

    public function testPrepareIndexViewModelReturnsEmptyResultOnValidationFailure(): void
    {
        $this->urlResolver->expects($this->never())->method($this->anything());
        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnCallback(
            static fn(?string $name = null, mixed $default = null): mixed => match ($name) {
                null => ['globalSearch' => 'x'],
                'page' => 1,
                'limit' => null,
                default => $default,
            },
        );

        $this->bookQueryService->expects($this->never())->method('searchPublished');

        $viewFactory = new BookSearchViewFactory(
            $this->bookQueryService,
            $this->createStub(BookDtoUrlResolver::class),
        );

        $result = $viewFactory->prepareIndexViewModel($request);

        $this->assertInstanceOf(BookIndexViewModel::class, $result);
        $this->assertInstanceOf(BookSearchForm::class, $result->searchModel);
        $this->assertInstanceOf(PagedResultDataProvider::class, $result->dataProvider);
        $this->assertTrue($result->searchModel->hasErrors());
    }

    public function testPrepareIndexViewModelReturnsDataOnSuccess(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('get')->willReturnCallback(
            static fn(?string $name = null, mixed $default = null): mixed => match ($name) {
                null => ['globalSearch' => 'query'],
                'page' => 1,
                'limit' => 9,
                default => $default,
            },
        );

        $dto = new BookReadDto(
            1,
            'Test Book',
            2021,
            'Description',
            self::ISBN_DEFAULT,
            [],
            [],
            'cover.jpg',
        );

        $pagedResult = $this->createStub(PagedResultInterface::class);
        $pagedResult->method('getModels')->willReturn([$dto]);
        $pagedResult->method('getTotalCount')->willReturn(1);
        $pagedResult->method('getPagination')->willReturn(new PaginationDto(1, 9, 1, 1));

        $this->bookQueryService->expects($this->once())
            ->method('searchPublished')
            ->with('query', 1, 9)
            ->willReturn($pagedResult);

        $resolvedDto = $dto->withCoverUrl('resolved.jpg');
        $this->urlResolver->expects($this->once())
            ->method('resolveUrl')
            ->with($dto)
            ->willReturn($resolvedDto);

        $result = $this->viewFactory->prepareIndexViewModel($request);

        $this->assertInstanceOf(BookIndexViewModel::class, $result);
        $this->assertInstanceOf(PagedResultDataProvider::class, $result->dataProvider);
        $this->assertFalse($result->searchModel->hasErrors());
        $models = $result->dataProvider->getModels();
        $this->assertCount(1, $models);
        $this->assertSame('resolved.jpg', $models[0]->coverUrl);
    }
}
