<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\handlers\AuthorViewDataFactory;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use yii\data\DataProviderInterface;
use yii\web\NotFoundHttpException;

final class AuthorViewDataFactoryTest extends Unit
{
    private AuthorQueryServiceInterface&MockObject $queryService;
    private AutoMapperInterface&MockObject $autoMapper;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private AuthorViewDataFactory $factory;

    protected function _before(): void
    {
        $this->queryService = $this->createMock(AuthorQueryServiceInterface::class);
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);

        $this->factory = new AuthorViewDataFactory(
            $this->queryService,
            $this->autoMapper,
            $this->dataProviderFactory,
        );
    }

    public function testGetIndexDataProviderReturnsProvider(): void
    {
        $pagedResult = $this->createMock(PagedResultInterface::class);
        $dataProvider = $this->createMock(DataProviderInterface::class);

        $this->queryService->expects($this->once())
            ->method('search')
            ->with('', 1, 20)
            ->willReturn($pagedResult);

        $this->dataProviderFactory->expects($this->once())
            ->method('create')
            ->with($pagedResult)
            ->willReturn($dataProvider);

        $result = $this->factory->getIndexDataProvider(1, 20);

        $this->assertSame($dataProvider, $result);
    }

    public function testGetAuthorForUpdateReturnsForm(): void
    {
        $dto = new AuthorReadDto(1, 'Test Author');
        $form = new AuthorForm();

        $this->queryService->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($dto, AuthorForm::class)
            ->willReturn($form);

        $result = $this->factory->getAuthorForUpdate(1);

        $this->assertSame($form, $result);
    }

    public function testGetAuthorForUpdateThrowsNotFound(): void
    {
        $this->queryService->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->factory->getAuthorForUpdate(999);
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
            ->with($dto, AuthorForm::class)
            ->willReturn(new \stdClass());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('AutoMapper returned unexpected type: expected');

        $this->factory->getAuthorForUpdate(1);
    }

    public function testGetAuthorViewReturnsDto(): void
    {
        $dto = new AuthorReadDto(1, 'Test Author');

        $this->queryService->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($dto);

        $result = $this->factory->getAuthorView(1);

        $this->assertSame($dto, $result);
    }

    public function testGetAuthorViewThrowsNotFound(): void
    {
        $this->queryService->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->factory->getAuthorView(999);
    }
}
