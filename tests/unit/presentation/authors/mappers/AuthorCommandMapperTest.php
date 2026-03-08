<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\mappers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\mappers\AuthorCommandMapper;
use app\presentation\common\mappers\AutoMapperContextBuilder;
use AutoMapper\AutoMapperInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AuthorCommandMapperTest extends TestCase
{
    private AutoMapperInterface&MockObject $autoMapper;
    private AutoMapperContextBuilder&MockObject $contextBuilder;
    private AuthorCommandMapper $mapper;

    protected function setUp(): void
    {
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->contextBuilder = $this->createMock(AutoMapperContextBuilder::class);
        $this->mapper = new AuthorCommandMapper($this->autoMapper, $this->contextBuilder);
    }

    public function testToCreateCommandMapsForm(): void
    {
        $this->contextBuilder->expects($this->never())->method($this->anything());
        $form = $this->createStub(AuthorForm::class);
        $command = $this->createStub(CreateAuthorCommand::class);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($form, CreateAuthorCommand::class)
            ->willReturn($command);

        $result = $this->mapper->toCreateCommand($form);

        $this->assertSame($command, $result);
    }

    public function testToUpdateCommandUsesContextBuilder(): void
    {
        $form = $this->createStub(AuthorForm::class);
        $command = $this->createStub(UpdateAuthorCommand::class);

        $context = ['constructor_arguments' => [UpdateAuthorCommand::class => ['id' => 5]]];

        $this->contextBuilder->expects($this->once())
            ->method('build')
            ->with([UpdateAuthorCommand::class => ['id' => 5]])
            ->willReturn($context);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with($form, UpdateAuthorCommand::class, $context)
            ->willReturn($command);

        $result = $this->mapper->toUpdateCommand(5, $form);

        $this->assertSame($command, $result);
    }
}
