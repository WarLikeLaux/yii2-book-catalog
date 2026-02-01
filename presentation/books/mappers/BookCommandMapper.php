<?php

declare(strict_types=1);

namespace app\presentation\books\mappers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\presentation\books\forms\BookForm;
use app\presentation\common\mappers\AutoMapperContextBuilder;
use AutoMapper\AutoMapperInterface;

final readonly class BookCommandMapper
{
    public function __construct(
        private AutoMapperInterface $autoMapper,
        private AutoMapperContextBuilder $contextBuilder,
    ) {
    }

    public function toCreateCommand(BookForm $form, string|null $cover): CreateBookCommand
    {
        /** @var CreateBookCommand $command */
        $command = $this->autoMapper->map(
            source: $form,
            target: CreateBookCommand::class,
            context: $this->contextBuilder->build([
                CreateBookCommand::class => ['storedCover' => $cover],
            ]),
        );

        return $command;
    }

    public function toUpdateCommand(int $id, BookForm $form, string|null $cover): UpdateBookCommand
    {
        /** @var UpdateBookCommand $command */
        $command = $this->autoMapper->map(
            source: $form,
            target: UpdateBookCommand::class,
            context: $this->contextBuilder->build([
                UpdateBookCommand::class => ['id' => $id, 'storedCover' => $cover],
            ]),
        );

        return $command;
    }
}
