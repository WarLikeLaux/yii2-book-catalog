<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\authors\queries\AuthorQueryService;
use app\application\books\queries\BookReadDto;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookViewDataFactory;
use app\presentation\books\mappers\BookFormMapper;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\web\NotFoundHttpException;

final class BookViewDataFactoryTest extends Unit
{
    private BookQueryServiceInterface&MockObject $bookQueryService;
    private AuthorQueryService&MockObject $authorQueryService;
    private BookFormMapper&MockObject $mapper;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private FileUrlResolver $resolver;
    private BookViewDataFactory $factory;

    protected function _before(): void
    {
        $this->bookQueryService = $this->createMock(BookQueryServiceInterface::class);
        $this->authorQueryService = $this->createMock(AuthorQueryService::class);
        $this->mapper = $this->createMock(BookFormMapper::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);
        $this->resolver = new FileUrlResolver('/uploads');

        $this->factory = new BookViewDataFactory(
            $this->bookQueryService,
            $this->authorQueryService,
            $this->mapper,
            $this->dataProviderFactory,
            $this->resolver
        );
    }

    public function testGetBookForUpdateThrowsNotFoundWhenBookNotExists(): void
    {
        $this->bookQueryService->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->factory->getBookForUpdate(999);
    }

    public function testGetBookForUpdateReturnsForm(): void
    {
        $dto = new BookReadDto(
            id: 1,
            title: 'Test',
            year: 2020,
            description: null,
            isbn: '9780132350884',
            authorIds: [],
            authorNames: [],
            coverUrl: null,
            isPublished: false,
            version: 1
        );

        $form = $this->createMock(BookForm::class);

        $this->bookQueryService->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $this->mapper->expects($this->once())
            ->method('toForm')
            ->with($dto)
            ->willReturn($form);

        $result = $this->factory->getBookForUpdate(1);

        $this->assertSame($form, $result);
    }

    public function testGetBookViewThrowsNotFoundWhenBookNotExists(): void
    {
        $this->bookQueryService->expects($this->once())
            ->method('findById')
            ->with(888)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->factory->getBookView(888);
    }

    public function testGetBookViewReturnsDtoWithResolvedUrl(): void
    {
        $dto = new BookReadDto(
            id: 2,
            title: 'Clean Code',
            year: 2008,
            description: 'A great book',
            isbn: '9780132350884',
            authorIds: [1],
            authorNames: ['Robert Martin'],
            coverUrl: 'cover.jpg',
            isPublished: true,
            version: 1
        );

        $this->bookQueryService->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($dto);

        $result = $this->factory->getBookView(2);

        $this->assertInstanceOf(BookReadDto::class, $result);
        $this->assertSame(2, $result->id);
        $this->assertSame('Clean Code', $result->title);
        $this->assertSame('/uploads/cover.jpg', $result->coverUrl);
    }

    public function testGetAuthorsListDelegatesToQueryService(): void
    {
        $expectedMap = [1 => 'Author A', 2 => 'Author B'];

        $this->authorQueryService->expects($this->once())
            ->method('getAuthorsMap')
            ->willReturn($expectedMap);

        $result = $this->factory->getAuthorsList();

        $this->assertSame($expectedMap, $result);
    }
}
