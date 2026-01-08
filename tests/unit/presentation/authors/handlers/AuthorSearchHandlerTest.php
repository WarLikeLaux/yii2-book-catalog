<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\queries\AuthorSearchCriteria;
use app\application\authors\queries\AuthorSearchResponse;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\authors\forms\AuthorSearchForm;
use app\presentation\authors\handlers\AuthorSearchHandler;
use app\presentation\authors\mappers\AuthorSearchCriteriaMapper;
use app\presentation\authors\mappers\AuthorSelect2Mapper;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AuthorSearchHandlerTest extends Unit
{
    private AuthorSearchCriteriaMapper|MockObject $criteriaMapper;
    private AuthorSelect2Mapper|MockObject $select2Mapper;
    private AuthorQueryServiceInterface|MockObject $queryService;
    private AuthorSearchHandler $handler;

    protected function _before(): void
    {
        $this->criteriaMapper = $this->createMock(AuthorSearchCriteriaMapper::class);
        $this->select2Mapper = $this->createMock(AuthorSelect2Mapper::class);
        $this->queryService = $this->createMock(AuthorQueryServiceInterface::class);

        $this->handler = new AuthorSearchHandler(
            $this->criteriaMapper,
            $this->select2Mapper,
            $this->queryService,
        );
    }

    public function testSearchReturnsEmptyResultWhenValidationFails(): void
    {
        $queryParams = ['invalid' => 'params'];
        $form = $this->createMock(AuthorSearchForm::class);
        $form->method('validate')->willReturn(false);

        $this->criteriaMapper->expects($this->once())
            ->method('toForm')
            ->with($queryParams)
            ->willReturn($form);

        $this->select2Mapper->expects($this->once())
            ->method('emptyResult')
            ->willReturn(['results' => []]);

        $this->queryService->expects($this->never())->method('search');

        $result = $this->handler->search($queryParams);

        $this->assertSame(['results' => []], $result);
    }

    public function testSearchReturnsMappedResultsWhenValidationPasses(): void
    {
        $queryParams = ['q' => 'test'];
        $form = $this->createMock(AuthorSearchForm::class);
        $form->method('validate')->willReturn(true);

        $this->criteriaMapper->expects($this->once())
            ->method('toForm')
            ->with($queryParams)
            ->willReturn($form);

        $criteria = new AuthorSearchCriteria('test', 1, 20);
        $this->criteriaMapper->expects($this->once())
            ->method('toCriteria')
            ->with($form)
            ->willReturn($criteria);

        $pagedResult = $this->createMock(PagedResultInterface::class);
        $pagedResult->method('getModels')->willReturn([]);
        $pagedResult->method('getTotalCount')->willReturn(0);

        $this->queryService->expects($this->once())
            ->method('search')
            ->with('test', 1, 20)
            ->willReturn($pagedResult);

        $expectedResult = ['results' => [['id' => 1, 'text' => 'Test Author']]];
        $this->select2Mapper->expects($this->once())
            ->method('mapToSelect2')
            ->with($this->isInstanceOf(AuthorSearchResponse::class))
            ->willReturn($expectedResult);

        $result = $this->handler->search($queryParams);

        $this->assertSame($expectedResult, $result);
    }
}
