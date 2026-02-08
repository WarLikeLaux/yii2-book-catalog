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
use app\presentation\authors\mappers\AuthorCommandMapper;
use app\presentation\common\services\WebOperationRunner;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

final class AuthorCommandHandlerTest extends Unit
{
    private const MSG_MAPPER_FAILED = 'mapper failed';
    private AuthorCommandMapper&MockObject $commandMapper;
    private CreateAuthorUseCase&MockObject $createAuthorUseCase;
    private UpdateAuthorUseCase&MockObject $updateAuthorUseCase;
    private DeleteAuthorUseCase&MockObject $deleteAuthorUseCase;
    private WebOperationRunner&MockObject $operationRunner;
    private AuthorCommandHandler $handler;

    protected function _before(): void
    {
        $this->commandMapper = $this->createMock(AuthorCommandMapper::class);
        $this->createAuthorUseCase = $this->createMock(CreateAuthorUseCase::class);
        $this->updateAuthorUseCase = $this->createMock(UpdateAuthorUseCase::class);
        $this->deleteAuthorUseCase = $this->createMock(DeleteAuthorUseCase::class);
        $this->operationRunner = $this->createMock(WebOperationRunner::class);

        $this->handler = new AuthorCommandHandler(
            $this->commandMapper,
            $this->createAuthorUseCase,
            $this->updateAuthorUseCase,
            $this->deleteAuthorUseCase,
            $this->operationRunner,
        );
    }

    public function testCreateAuthorReturnsIdOnSuccess(): void
    {
        $form = $this->createMock(AuthorForm::class);
        $command = $this->createMock(CreateAuthorCommand::class);

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
        $form = $this->createMock(AuthorForm::class);

        $this->commandMapper->expects($this->once())
            ->method('toCreateCommand')
            ->willThrowException(new RuntimeException(self::MSG_MAPPER_FAILED));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::MSG_MAPPER_FAILED);

        $this->handler->createAuthor($form);
    }

    public function testUpdateAuthorSucceeds(): void
    {
        $form = $this->createMock(AuthorForm::class);
        $command = $this->createMock(UpdateAuthorCommand::class);

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
        $form = $this->createMock(AuthorForm::class);

        $this->commandMapper->expects($this->once())
            ->method('toUpdateCommand')
            ->willThrowException(new RuntimeException(self::MSG_MAPPER_FAILED));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::MSG_MAPPER_FAILED);

        $this->handler->updateAuthor(1, $form);
    }

    public function testDeleteAuthorSucceeds(): void
    {
        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate');

        $this->handler->deleteAuthor(1);
    }
}
