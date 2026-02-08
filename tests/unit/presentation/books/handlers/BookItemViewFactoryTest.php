<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\books\queries\BookReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\dto\BookEditViewModel;
use app\presentation\books\handlers\BookItemViewFactory;
use app\presentation\books\mappers\BookViewModelMapper;
use app\presentation\books\services\BookDtoUrlResolver;
use app\presentation\services\FileUrlResolver;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\web\NotFoundHttpException;

final class BookItemViewFactoryTest extends Unit
{
    private BookQueryServiceInterface&MockObject $finder;
    private AuthorQueryServiceInterface&MockObject $authorQueryService;
    private FileUrlResolver $resolver;
    private BookDtoUrlResolver $urlResolver;
    private BookItemViewFactory $factory;

    protected function _before(): void
    {
        $this->finder = $this->createMock(BookQueryServiceInterface::class);
        $this->authorQueryService = $this->createMock(AuthorQueryServiceInterface::class);
        $this->resolver = new FileUrlResolver('/uploads');
        $this->urlResolver = new BookDtoUrlResolver($this->resolver);

        $this->factory = new BookItemViewFactory(
            $this->finder,
            $this->authorQueryService,
            $this->urlResolver,
            new BookViewModelMapper(),
        );
    }

    public function testGetCreateViewModelReturnsModel(): void
    {
        $authors = [
            new AuthorReadDto(1, 'Author A'),
        ];

        $this->authorQueryService->expects($this->once())
            ->method('findAllOrderedByFio')
            ->willReturn($authors);

        $result = $this->factory->getCreateViewModel();

        $this->assertInstanceOf(BookEditViewModel::class, $result);
        $this->assertSame([1 => 'Author A'], $result->authors);
    }

    public function testGetUpdateViewModelReturnsModel(): void
    {

        $dto = new BookReadDto(
            1,
            'T',
            2020,
            null,
            'ISBN',
            [],
            [],
            null,
            'draft',
            1,
        );

        $authors = [];

        $this->finder->expects($this->once()) // Single query: reuse dto for form + view
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $this->authorQueryService->expects($this->once())
            ->method('findAllOrderedByFio')
            ->willReturn($authors);

        $result = $this->factory->getUpdateViewModel(1);

        $this->assertInstanceOf(BookEditViewModel::class, $result);

        $this->assertEquals($dto->id, $result->form->id);
        $this->assertEquals($dto->title, $result->form->title);

        $this->assertSame($dto->id, $result->book?->id);
    }

    public function testGetBookViewThrowsNotFound(): void
    {

        $this->finder->expects($this->once())

                ->method('findById')

                ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->factory->getBookView(999);
    }
}
