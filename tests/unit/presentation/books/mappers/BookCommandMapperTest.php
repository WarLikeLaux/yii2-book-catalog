<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\mappers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\domain\values\StoredFileReference;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookCommandMapper;
use app\presentation\common\mappers\AutoMapperContextBuilder;
use AutoMapper\AutoMapperInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class BookCommandMapperTest extends Unit
{
    private AutoMapperInterface&MockObject $autoMapper;
    private AutoMapperContextBuilder&MockObject $contextBuilder;
    private BookCommandMapper $mapper;

    protected function _before(): void
    {
        $this->autoMapper = $this->createMock(AutoMapperInterface::class);
        $this->contextBuilder = $this->createMock(AutoMapperContextBuilder::class);
        $this->mapper = new BookCommandMapper($this->autoMapper, $this->contextBuilder);
    }

    public function testToCreateCommandMapsForm(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->title = 'Test';
        $form->year = '2024';
        $form->description = 'Desc';
        $form->isbn = '9780132350884';
        $form->authorIds = ['1', 2, 0, 'bad'];
        $form->version = 1;

        $cover = $this->createMock(StoredFileReference::class);
        $command = $this->createMock(CreateBookCommand::class);
        $context = ['constructor_arguments' => [CreateBookCommand::class => ['storedCover' => $cover]]];

        $this->contextBuilder->expects($this->once())
            ->method('build')
            ->with([CreateBookCommand::class => ['storedCover' => $cover]])
            ->willReturn($context);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with(
                $this->callback(static fn (object $source): bool => $source === $form),
                CreateBookCommand::class,
                $context,
            )
            ->willReturn($command);

        $result = $this->mapper->toCreateCommand($form, $cover);

        $this->assertSame($command, $result);
    }

    public function testToUpdateCommandMapsForm(): void
    {
        $form = $this->createMock(BookForm::class);
        $form->title = 'Test';
        $form->year = 2024;
        $form->description = '';
        $form->isbn = 9780132350884;
        $form->authorIds = 3;
        $form->version = 3;

        $cover = $this->createMock(StoredFileReference::class);
        $command = $this->createMock(UpdateBookCommand::class);
        $context = ['constructor_arguments' => [UpdateBookCommand::class => ['id' => 10, 'storedCover' => $cover]]];

        $this->contextBuilder->expects($this->once())
            ->method('build')
            ->with([UpdateBookCommand::class => ['id' => 10, 'storedCover' => $cover]])
            ->willReturn($context);

        $this->autoMapper->expects($this->once())
            ->method('map')
            ->with(
                $this->callback(static fn (object $source): bool => $source === $form),
                UpdateBookCommand::class,
                $context,
            )
            ->willReturn($command);

        $result = $this->mapper->toUpdateCommand(10, $form, $cover);

        $this->assertSame($command, $result);
    }
}
