<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\application\common\exceptions\ApplicationException;
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

        $this->operationRunner->method('executeWithFormErrors')->willReturn(123);

        $this->assertSame(123, $this->handler->createAuthor($form));
    }

    public function testCreateAuthorReturnsNullOnMappingError(): void
    {
        $form = $this->createForm(['fio'], ['fio' => 'Invalid']);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $this->expectFormError($form);
        $this->assertNull($this->handler->createAuthor($form));
    }

    public function testUpdateAuthorReturnsTrueOnSuccess(): void
    {
        $form = $this->createMock(AuthorForm::class);
        $command = $this->createMock(UpdateAuthorCommand::class);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn($command);

        $this->operationRunner->method('executeWithFormErrors')->willReturn(true);

        $this->assertTrue($this->handler->updateAuthor(1, $form));
    }

    public function testUpdateAuthorReturnsFalseOnMappingError(): void
    {
        $form = $this->createForm(['fio'], []);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn(null);

        $this->expectFormError($form);
        $this->assertFalse($this->handler->updateAuthor(1, $form));
    }

    public function testCreateAuthorAddsFormErrorOnApplicationException(): void
    {
        $form = $this->createForm(['fio'], ['fio' => 'Duplicate']);
        $command = $this->createMock(CreateAuthorCommand::class);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn($command);

        $this->mockOperationRunnerDomainError(new ApplicationException('author.error.fio_exists'));

        $form->expects($this->once())
            ->method('addError')
            ->with('fio', $this->anything());

        $this->handler->createAuthor($form);
    }

    public function testCreateAuthorAddsFioErrorWhenNoAttributes(): void
    {
        $form = $this->createForm([], ['fio' => 'Duplicate']);
        $command = $this->createMock(CreateAuthorCommand::class);

        $this->operationRunner->expects($this->once())
            ->method('runStep')
            ->willReturn($command);

        $this->mockOperationRunnerDomainError(new ApplicationException('author.error.fio_exists'));

        $form->expects($this->once())
            ->method('addError')
            ->with('fio', $this->anything());

        $this->handler->createAuthor($form);
    }

    public function testDeleteAuthorReturnsTrueOnSuccess(): void
    {
        $this->operationRunner->method('execute')->willReturn(true);
        $this->assertTrue($this->handler->deleteAuthor(1));
    }

    private function createForm(array $attributes, array $data): AuthorForm&MockObject
    {
        $form = $this->createMock(AuthorForm::class);
        $form->method('attributes')->willReturn($attributes);
        $form->method('toArray')->willReturn($data);

        return $form;
    }

    private function mockOperationRunnerDomainError(ApplicationException $exception): void
    {
        $this->operationRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function (mixed $_, mixed $__, mixed $___, $onDomainError) use ($exception) {
                $onDomainError($exception);
                return null;
            });
    }

    private function expectFormError(AuthorForm&MockObject $form): void
    {
        $form->expects($this->once())->method('addError');
    }
}
