<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\authors\queries\AuthorSearchCriteria;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\authors\forms\AuthorSearchForm;
use app\presentation\authors\handlers\AuthorSearchHandler;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AuthorSearchHandlerTest extends Unit
{
    private AutoMapperInterface&MockObject $autoMapper;
    private AuthorQueryServiceInterface&MockObject $queryService;
    private AuthorSearchHandler $handler;

    protected function _before(): void
    {
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->queryService = $this->createMock(AuthorQueryServiceInterface::class);

        $this->handler = new AuthorSearchHandler(
            $this->queryService,
            $this->autoMapper,
        );
    }

    public function testSearchReturnsEmptyResultWhenValidationFails(): void
    {
        $queryParams = ['q' => str_repeat('a', 256)];

        $this->queryService->expects($this->never())->method('search');

        $result = $this->handler->search($queryParams);

        $expectedResult = [
            'results' => [],
            'pagination' => [
                'more' => false,
            ],
        ];

        $this->assertSame($expectedResult, $result);
    }

    public function testSearchReturnsMappedResultsWhenValidationPasses(): void
    {
        $queryParams = ['q' => 'test'];

        $criteria = new AuthorSearchCriteria('test', 1, 20);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($this->isInstanceOf(AuthorSearchForm::class), AuthorSearchCriteria::class)
            ->willReturn($criteria);

        $pagedResult = $this->createMock(PagedResultInterface::class);
        $pagedResult->method('getModels')->willReturn([
            new AuthorReadDto(1, 'Test Author'),
        ]);
        $pagedResult->method('getTotalCount')->willReturn(1);

        $this->queryService->expects($this->once())
            ->method('search')
            ->with('test', 1, 20)
            ->willReturn($pagedResult);

        $result = $this->handler->search($queryParams);

        $expectedResult = [
            'results' => [
                ['id' => 1, 'text' => 'Test Author'],
            ],
            'pagination' => ['more' => false],
        ];

        $this->assertSame($expectedResult, $result);
    }
}
