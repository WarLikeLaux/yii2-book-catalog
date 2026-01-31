<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\dto\AuthorEditViewModel;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\handlers\AuthorItemViewFactory;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\web\NotFoundHttpException;

final class AuthorItemViewFactoryTest extends Unit
{
    private AuthorQueryServiceInterface&MockObject $queryService;
    private AutoMapperInterface&MockObject $autoMapper;
    private AuthorItemViewFactory $factory;

    protected function _before(): void
    {
        $this->queryService = $this->createMock(AuthorQueryServiceInterface::class);
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);

        $this->factory = new AuthorItemViewFactory(
            $this->queryService,
            $this->autoMapper,
        );
    }

    public function testGetCreateViewModel(): void
    {
        $result = $this->factory->getCreateViewModel();
        $this->assertInstanceOf(AuthorEditViewModel::class, $result);
    }

    public function testGetUpdateViewModel(): void
    {
        $dto = new AuthorReadDto(1, 'Author');
        $form = new AuthorForm($this->queryService);

        $this->queryService->expects($this->exactly(2))
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($dto, $this->isInstanceOf(AuthorForm::class))
            ->willReturn($form);

        $result = $this->factory->getUpdateViewModel(1);

        $this->assertInstanceOf(AuthorEditViewModel::class, $result);
        $this->assertSame($form, $result->form);
        $this->assertSame($dto, $result->author);
    }

    public function testGetAuthorForUpdateThrowsWhenAutoMapperReturnsWrongType(): void
    {
        $dto = new AuthorReadDto(1, 'Test Author');

        $this->queryService->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->willReturn(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->factory->getAuthorForUpdate(1);
    }

    public function testGetAuthorViewThrowsNotFound(): void
    {
        $this->queryService->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->factory->getAuthorView(999);
    }
}
