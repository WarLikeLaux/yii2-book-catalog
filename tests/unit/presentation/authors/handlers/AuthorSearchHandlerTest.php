<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\queries\AuthorQueryService;
use app\application\authors\queries\AuthorSearchCriteria;
use app\application\authors\queries\AuthorSearchResponse;
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
    private AuthorQueryService|MockObject $queryService;
    private AuthorSearchHandler $handler;

    protected function _before(): void
    {
        $this->criteriaMapper = $this->createMock(AuthorSearchCriteriaMapper::class);
        $this->select2Mapper = $this->createMock(AuthorSelect2Mapper::class);
        $this->queryService = $this->createMock(AuthorQueryService::class);

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

        $criteria = $this->createMock(AuthorSearchCriteria::class);
        $this->criteriaMapper->expects($this->once())
            ->method('toCriteria')
            ->with($form)
            ->willReturn($criteria);

        $queryResult = new AuthorSearchResponse([], 0, 1, 20);
        $this->queryService->expects($this->once())
            ->method('search')
            ->with($criteria)
            ->willReturn($queryResult);

        $expectedResult = ['results' => [['id' => 1, 'text' => 'Test Author']]];
        $this->select2Mapper->expects($this->once())
            ->method('mapToSelect2')
            ->with($queryResult)
            ->willReturn($expectedResult);

        $result = $this->handler->search($queryParams);

        $this->assertSame($expectedResult, $result);
    }
}
