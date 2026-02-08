<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\presentation\authors\handlers\AuthorSearchViewFactory;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AuthorSearchViewFactoryTest extends Unit
{
    private AuthorQueryServiceInterface&MockObject $queryService;
    private AuthorSearchViewFactory $viewFactory;

    protected function _before(): void
    {
        $this->queryService = $this->createMock(AuthorQueryServiceInterface::class);

        $this->viewFactory = new AuthorSearchViewFactory(
            $this->queryService,
        );
    }

    public function testSearchReturnsEmptyResultWhenValidationFails(): void
    {
        $queryParams = ['q' => str_repeat('a', 256)];

        $this->queryService->expects($this->never())->method('search');

        $result = $this->viewFactory->search($queryParams);

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

        $pagedResult = $this->createMock(PagedResultInterface::class);
        $pagedResult->method('getModels')->willReturn([
            new AuthorReadDto(1, 'Test Author'),
        ]);
        $pagedResult->method('getTotalCount')->willReturn(1);

        $this->queryService->expects($this->once())
            ->method('search')
            ->with('test', 1, 20)
            ->willReturn($pagedResult);

        $result = $this->viewFactory->search($queryParams);

        $expectedResult = [
            'results' => [
                ['id' => 1, 'text' => 'Test Author'],
            ],
            'pagination' => ['more' => false],
        ];

        $this->assertSame($expectedResult, $result);
    }
}
