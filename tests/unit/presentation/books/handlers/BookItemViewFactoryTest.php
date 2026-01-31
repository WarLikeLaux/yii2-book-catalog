<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\books\queries\BookReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\dto\BookEditViewModel;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookItemViewFactory;
use app\presentation\services\FileUrlResolver;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\web\NotFoundHttpException;

final class BookItemViewFactoryTest extends Unit
{
    private BookQueryServiceInterface&MockObject $finder;
    private AuthorQueryServiceInterface&MockObject $authorQueryService;
    private AutoMapperInterface&MockObject $autoMapper;
    private FileUrlResolver $resolver;
    private BookItemViewFactory $factory;

    protected function _before(): void
    {
        $this->finder = $this->createMock(BookQueryServiceInterface::class);
        $this->authorQueryService = $this->createMock(AuthorQueryServiceInterface::class);
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->resolver = new FileUrlResolver('/uploads');

        $this->factory = new BookItemViewFactory(
            $this->finder,
            $this->authorQueryService,
            $this->autoMapper,
            $this->resolver,
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
            false,
            1,
        );

        $form = $this->createMock(BookForm::class);

        $authors = [];

        $this->finder->expects($this->exactly(2)) // Called by getBookForUpdate (if no form) and getBookView

                ->method('findById')

                ->with(1)

                ->willReturn($dto);

        $this->autoMapper->expects($this->once())

                ->method('map')

                ->with($dto, $this->isInstanceOf(BookForm::class))

                ->willReturn($form);

        $this->authorQueryService->expects($this->once())

                ->method('findAllOrderedByFio')

                ->willReturn($authors);

        $result = $this->factory->getUpdateViewModel(1);

        $this->assertInstanceOf(BookEditViewModel::class, $result);

        $this->assertSame($form, $result->form);

        $this->assertSame($dto->id, $result->book?->id);
    }

    public function testGetBookForUpdateThrowsWhenAutoMapperReturnsWrongType(): void
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
            false,
            1,
        );

        $this->finder->expects($this->once())

                ->method('findById')

                ->willReturn($dto);

        $this->autoMapper->expects($this->once())

                ->method('map')

                ->willReturn(new \stdClass());

        $this->expectException(\LogicException::class);

        $this->factory->getBookForUpdate(1);
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
