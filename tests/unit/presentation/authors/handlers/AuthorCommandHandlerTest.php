<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\handlers\AuthorCommandHandler;
use app\presentation\authors\handlers\AuthorUseCases;
use app\presentation\authors\mappers\AuthorCommandMapper;
use app\presentation\common\services\WebOperationRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuthorCommandHandlerTest extends TestCase
{
    private const MSG_MAPPER_FAILED = 'mapper failed';
    private AuthorCommandMapper&MockObject $commandMapper;
    private AuthorUseCases $useCases;
    private WebOperationRunner&MockObject $operationRunner;
    private AuthorCommandHandler $handler;

    protected function setUp(): void
    {
        $this->commandMapper = $this->createMock(AuthorCommandMapper::class);
        $this->useCases = new AuthorUseCases(
            $this->createStub(CreateAuthorUseCase::class),
            $this->createStub(UpdateAuthorUseCase::class),
            $this->createStub(DeleteAuthorUseCase::class),
        );
        $this->operationRunner = $this->createMock(WebOperationRunner::class);

        $this->handler = new AuthorCommandHandler(
            $this->commandMapper,
            $this->useCases,
            $this->operationRunner,
        );
    }

    public function testCreateAuthorReturnsIdOnSuccess(): void
    {
        $form = $this->createStub(AuthorForm::class);
        $command = $this->createStub(CreateAuthorCommand::class);

        $this->commandMapper->expects($this->once())
            ->method('toCreateCommand')
            ->with($form)
            ->willReturn($command);

        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate')
            ->willReturn(123);

        $this->assertSame(123, $this->handler->createAuthor($form));
    }

    public function testCreateAuthorPropagatesMapperException(): void
    {
        $this->operationRunner->expects($this->never())->method($this->anything());
        $form = $this->createStub(AuthorForm::class);
        $operationRunner = $this->createStub(WebOperationRunner::class);

        $this->commandMapper->expects($this->once())
            ->method('toCreateCommand')
            ->willThrowException(new RuntimeException(self::MSG_MAPPER_FAILED));

        $handler = new AuthorCommandHandler(
            $this->commandMapper,
            $this->useCases,
            $operationRunner,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::MSG_MAPPER_FAILED);

        $handler->createAuthor($form);
    }

    public function testUpdateAuthorSucceeds(): void
    {
        $form = $this->createStub(AuthorForm::class);
        $command = $this->createStub(UpdateAuthorCommand::class);

        $this->commandMapper->expects($this->once())
            ->method('toUpdateCommand')
            ->with(1, $form)
            ->willReturn($command);

        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate');

        $this->handler->updateAuthor(1, $form);
    }

    public function testUpdateAuthorPropagatesMapperException(): void
    {
        $this->operationRunner->expects($this->never())->method($this->anything());
        $form = $this->createStub(AuthorForm::class);
        $operationRunner = $this->createStub(WebOperationRunner::class);

        $this->commandMapper->expects($this->once())
            ->method('toUpdateCommand')
            ->willThrowException(new RuntimeException(self::MSG_MAPPER_FAILED));

        $handler = new AuthorCommandHandler(
            $this->commandMapper,
            $this->useCases,
            $operationRunner,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::MSG_MAPPER_FAILED);

        $handler->updateAuthor(1, $form);
    }

    public function testDeleteAuthorSucceeds(): void
    {
        $this->commandMapper->expects($this->never())->method($this->anything());
        $commandMapper = $this->createStub(AuthorCommandMapper::class);

        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate');

        $handler = new AuthorCommandHandler(
            $commandMapper,
            $this->useCases,
            $this->operationRunner,
        );

        $handler->deleteAuthor(1);
    }
}
