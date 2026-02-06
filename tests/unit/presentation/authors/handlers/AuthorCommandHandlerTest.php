<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\common\exceptions\OperationFailedException;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\handlers\AuthorCommandHandler;
use app\presentation\authors\mappers\AuthorCommandMapper;
use app\presentation\common\services\WebOperationRunner;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AuthorCommandHandlerTest extends Unit
{
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

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn($command);

        $this->operationRunner->method('executeAndPropagate')->willReturn(123);

        $this->assertSame(123, $this->handler->createAuthor($form));
    }

    public function testCreateAuthorThrowsOnMappingError(): void
    {
        $form = $this->createMock(AuthorForm::class);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage('error.internal_mapper_failed');

        $this->handler->createAuthor($form);
    }

    public function testUpdateAuthorSucceeds(): void
    {
        $form = $this->createMock(AuthorForm::class);
        $command = $this->createMock(UpdateAuthorCommand::class);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn($command);

        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate');

        $this->handler->updateAuthor(1, $form);
        $this->assertTrue(true);
    }

    public function testUpdateAuthorThrowsOnMappingError(): void
    {
        $form = $this->createMock(AuthorForm::class);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage('error.internal_mapper_failed');

        $this->handler->updateAuthor(1, $form);
    }

    public function testDeleteAuthorSucceeds(): void
    {
        $this->operationRunner->expects($this->once())
            ->method('executeAndPropagate');

        $this->handler->deleteAuthor(1);
        $this->assertTrue(true);
    }
}
