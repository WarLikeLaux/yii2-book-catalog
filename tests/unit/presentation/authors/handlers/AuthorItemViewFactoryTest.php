<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\dto\AuthorEditViewModel;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\handlers\AuthorItemViewFactory;
use AutoMapper\AutoMapperInterface;
use PHPUnit\Framework\TestCase;
use yii\web\NotFoundHttpException;

final class AuthorItemViewFactoryTest extends TestCase
{
    public function testGetCreateViewModel(): void
    {
        $factory = new AuthorItemViewFactory(
            $this->createStub(AuthorQueryServiceInterface::class),
            $this->createStub(AutoMapperInterface::class),
        );

        $result = $factory->getCreateViewModel();
        $this->assertInstanceOf(AuthorEditViewModel::class, $result);
    }

    public function testGetUpdateViewModel(): void
    {
        $dto = new AuthorReadDto(1, 'Author');
        $form = new AuthorForm();

        $queryService = $this->createMock(AuthorQueryServiceInterface::class);
        $queryService->expects($this->exactly(2))
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $autoMapper = $this->createMock(AutoMapperInterface::class);
        $autoMapper->expects($this->once())
            ->method('map')
            ->with($dto, $this->isInstanceOf(AuthorForm::class))
            ->willReturn($form);

        $factory = new AuthorItemViewFactory($queryService, $autoMapper);

        $result = $factory->getUpdateViewModel(1);

        $this->assertInstanceOf(AuthorEditViewModel::class, $result);
        $this->assertSame($form, $result->form);
        $this->assertSame($dto, $result->author);
    }

    public function testGetAuthorForUpdateThrowsWhenAutoMapperReturnsWrongType(): void
    {
        $dto = new AuthorReadDto(1, 'Test Author');

        $queryService = $this->createMock(AuthorQueryServiceInterface::class);
        $queryService->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $autoMapper = $this->createMock(AutoMapperInterface::class);
        $autoMapper->expects($this->once())
            ->method('map')
            ->willReturn(new \stdClass());

        $factory = new AuthorItemViewFactory($queryService, $autoMapper);

        $this->expectException(\TypeError::class);
        $factory->getAuthorForUpdate(1);
    }

    public function testGetAuthorViewThrowsNotFound(): void
    {
        $queryService = $this->createMock(AuthorQueryServiceInterface::class);
        $queryService->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $factory = new AuthorItemViewFactory(
            $queryService,
            $this->createStub(AutoMapperInterface::class),
        );

        $this->expectException(NotFoundHttpException::class);
        $factory->getAuthorView(999);
    }
}
