<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\handlers\AuthorViewDataFactory;
use app\presentation\authors\mappers\AuthorFormMapper;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use yii\data\DataProviderInterface;
use yii\web\NotFoundHttpException;

final class AuthorViewDataFactoryTest extends Unit
{
    private AuthorQueryServiceInterface&MockObject $queryService;
    private AuthorFormMapper&MockObject $mapper;
    private PagedResultDataProviderFactory&MockObject $dataProviderFactory;
    private AuthorViewDataFactory $factory;

    protected function _before(): void
    {
        $this->queryService = $this->createMock(AuthorQueryServiceInterface::class);
        $this->mapper = $this->createMock(AuthorFormMapper::class);
        $this->dataProviderFactory = $this->createMock(PagedResultDataProviderFactory::class);

        $this->factory = new AuthorViewDataFactory(
            $this->queryService,
            $this->mapper,
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

        $this->mapper->expects($this->once())
            ->method('toForm')
            ->with($dto)
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
