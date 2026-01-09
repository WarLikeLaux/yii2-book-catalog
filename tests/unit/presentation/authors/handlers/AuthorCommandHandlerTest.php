<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\handlers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\handlers\AuthorCommandHandler;
use app\presentation\common\services\WebUseCaseRunner;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class AuthorCommandHandlerTest extends Unit
{
    private AutoMapperInterface&MockObject $autoMapper;
    private CreateAuthorUseCase&MockObject $createAuthorUseCase;
    private UpdateAuthorUseCase&MockObject $updateAuthorUseCase;
    private DeleteAuthorUseCase&MockObject $deleteAuthorUseCase;
    private WebUseCaseRunner&MockObject $useCaseRunner;
    private AuthorCommandHandler $handler;

    protected function _before(): void
    {
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->createAuthorUseCase = $this->createMock(CreateAuthorUseCase::class);
        $this->updateAuthorUseCase = $this->createMock(UpdateAuthorUseCase::class);
        $this->deleteAuthorUseCase = $this->createMock(DeleteAuthorUseCase::class);
        $this->useCaseRunner = $this->createMock(WebUseCaseRunner::class);

        $this->handler = new AuthorCommandHandler(
            $this->autoMapper,
            $this->createAuthorUseCase,
            $this->updateAuthorUseCase,
            $this->deleteAuthorUseCase,
            $this->useCaseRunner,
        );
    }

    public function testCreateAuthorReturnsIdOnSuccess(): void
    {
        $form = $this->createMock(AuthorForm::class);
        $command = $this->createMock(CreateAuthorCommand::class);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($form, CreateAuthorCommand::class)
            ->willReturn($command);

        $this->useCaseRunner->method('executeWithFormErrors')->willReturn(123);

        $this->assertSame(123, $this->handler->createAuthor($form));
    }

    public function testCreateAuthorReturnsNullOnMappingError(): void
    {
        $form = $this->createMock(AuthorForm::class);
        $form->method('attributes')->willReturn(['fio']);
        $this->autoMapper->method('map')->willThrowException(new \RuntimeException('Mapping failed'));

        $form->expects($this->once())->method('addError');

        $this->assertNull($this->handler->createAuthor($form));
    }

    public function testUpdateAuthorReturnsTrueOnSuccess(): void
    {
        $form = $this->createMock(AuthorForm::class);
        $form->method('toArray')->willReturn(['fio' => 'New Name']);
        $command = $this->createMock(UpdateAuthorCommand::class);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->willReturn($command);

        $this->useCaseRunner->method('executeWithFormErrors')->willReturn(true);

        $this->assertTrue($this->handler->updateAuthor(1, $form));
    }

    public function testUpdateAuthorReturnsFalseOnMappingError(): void
    {
        $form = $this->createMock(AuthorForm::class);
        $form->method('attributes')->willReturn(['fio']);
        $form->method('toArray')->willReturn([]);
        $this->autoMapper->method('map')->willThrowException(new \RuntimeException('Mapping failed'));

        $form->expects($this->once())->method('addError');

        $this->assertFalse($this->handler->updateAuthor(1, $form));
    }

    public function testCreateAuthorAddsFormErrorOnDomainException(): void
    {
        $form = $this->createMock(AuthorForm::class);
        $command = $this->createMock(CreateAuthorCommand::class);
        $this->autoMapper->method('map')->willReturn($command);

        $exception = new ValidationException(DomainErrorCode::AuthorFioExists);

        $this->useCaseRunner->expects($this->once())
            ->method('executeWithFormErrors')
            ->willReturnCallback(static function (mixed $_, mixed $__, mixed $___, $onDomainError) use ($exception) {
                $onDomainError($exception);
                return null;
            });

        $form->expects($this->once())
            ->method('addError')
            ->with('fio', $this->anything());

        $this->handler->createAuthor($form);
    }

    public function testDeleteAuthorReturnsTrueOnSuccess(): void
    {
        $this->useCaseRunner->method('execute')->willReturn(true);
        $this->assertTrue($this->handler->deleteAuthor(1));
    }
}
