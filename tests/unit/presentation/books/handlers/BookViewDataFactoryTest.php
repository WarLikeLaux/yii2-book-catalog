<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\books\queries\BookReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookFinderInterface;
use app\application\ports\BookSearcherInterface;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookViewDataFactory;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use app\presentation\services\FileUrlResolver;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use yii\web\NotFoundHttpException;

final class BookViewDataFactoryTest extends Unit
{
    private BookFinderInterface&MockObject $finder;
    private BookSearcherInterface&MockObject $searcher;
    private AuthorQueryServiceInterface&MockObject $authorQueryService;
    private AutoMapperInterface&MockObject $autoMapper;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private FileUrlResolver $resolver;
    private BookViewDataFactory $factory;

    protected function _before(): void
    {
        $this->finder = $this->createMock(BookFinderInterface::class);
        $this->searcher = $this->createMock(BookSearcherInterface::class);
        $this->authorQueryService = $this->createMock(AuthorQueryServiceInterface::class);
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);
        $this->resolver = new FileUrlResolver('/uploads');

        $this->factory = new BookViewDataFactory(
            $this->finder,
            $this->searcher,
            $this->authorQueryService,
            $this->autoMapper,
            $this->dataProviderFactory,
            $this->resolver,
        );
    }

    public function testGetBookForUpdateThrowsNotFoundWhenBookNotExists(): void
    {
        $this->finder->expects($this->once())
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
            version: 1,
        );

        $form = $this->createMock(BookForm::class);

        $this->finder->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($dto, BookForm::class)
            ->willReturn($form);

        $result = $this->factory->getBookForUpdate(1);

        $this->assertSame($form, $result);
    }

    public function testGetBookForUpdateThrowsWhenAutoMapperReturnsWrongType(): void
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
            version: 1,
        );

        $this->finder->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($dto, BookForm::class)
            ->willReturn(new \stdClass());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'AutoMapper не смог преобразовать %s в %s в getBookForUpdate: получен %s. Проверьте конфигурацию маппера.',
            BookReadDto::class,
            BookForm::class,
            'stdClass',
        ));

        $this->factory->getBookForUpdate(1);
    }

    public function testGetBookViewThrowsNotFoundWhenBookNotExists(): void
    {
        $this->finder->expects($this->once())
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
            version: 1,
        );

        $this->finder->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($dto);

        $result = $this->factory->getBookView(2);

        $this->assertInstanceOf(BookReadDto::class, $result);
        $this->assertSame(2, $result->id);
        $this->assertSame('Clean Code', $result->title);
        $this->assertSame('/uploads/cover.jpg', $result->coverUrl);
    }

    public function testGetAuthorsListReturnsMapFromPort(): void
    {
        $authors = [
            new AuthorReadDto(1, 'Author A'),
            new AuthorReadDto(2, 'Author B'),
        ];

        $this->authorQueryService->expects($this->once())
            ->method('findAllOrderedByFio')
            ->willReturn($authors);

        $result = $this->factory->getAuthorsList();

        $this->assertSame([1 => 'Author A', 2 => 'Author B'], $result);
    }
}
