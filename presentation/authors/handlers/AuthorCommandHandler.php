<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\common\handlers\UseCaseHandlerTrait;
use app\presentation\common\services\WebUseCaseRunner;
use AutoMapper\AutoMapperInterface;
use Yii;

/**
 * NOTE: Прагматичный компромисс: группировка всех команд сущности в одном классе.
 * @see docs/DECISIONS.md (см. пункт "3. Группировка хендлеров по сущностям")
 */
final readonly class AuthorCommandHandler
{
    use UseCaseHandlerTrait;

    /** @noRector \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateClassConstantRector */
    private const array ERROR_TO_FIELD_MAP = [
        'author.error.fio_exists' => 'fio',
        'author.error.fio_empty' => 'fio',
        'author.error.fio_too_short' => 'fio',
        'author.error.fio_too_long' => 'fio',
    ];

    /**
     * Initialize the handler with its required dependencies.
     *
     * @param AutoMapperInterface $autoMapper Maps form data to command DTOs.
     * @param CreateAuthorUseCase $createAuthorUseCase Use case for creating authors.
     * @param UpdateAuthorUseCase $updateAuthorUseCase Use case for updating authors.
     * @param DeleteAuthorUseCase $deleteAuthorUseCase Use case for deleting authors.
     * @param WebUseCaseRunner $useCaseRunner Executes use cases with form handling and messaging.
     */
    public function __construct(
        private AutoMapperInterface $autoMapper,
        private CreateAuthorUseCase $createAuthorUseCase,
        private UpdateAuthorUseCase $updateAuthorUseCase,
        private DeleteAuthorUseCase $deleteAuthorUseCase,
        private WebUseCaseRunner $useCaseRunner,
    ) {
    }

    /**
     * Create a new author from the provided form data.
     *
     * @param AuthorForm $form Form containing the author's input data.
     * @return int|null The ID of the created author, or `null` if creation failed.
     */
    public function createAuthor(AuthorForm $form): ?int
    {
        /** @var CreateAuthorCommand $command */
        $command = $this->autoMapper->map($form, CreateAuthorCommand::class);

        /** @var int|null $result */
        $result = $this->executeWithForm(
            $this->useCaseRunner,
            $form,
            $command,
            $this->createAuthorUseCase,
            Yii::t('app', 'author.success.created'),
        );

        return $result;
    }

    /**
     * Updates an existing author with data from the provided form.
     *
     * @param int $id The author's identifier.
     * @param AuthorForm $form The form containing updated author data.
     * @return bool `true` if the author was updated, `false` otherwise.
     */
    public function updateAuthor(int $id, AuthorForm $form): bool
    {
        /** @var UpdateAuthorCommand $command */
        $command = $this->autoMapper->map(['id' => $id] + $form->toArray(), UpdateAuthorCommand::class);

        $result = $this->executeWithForm(
            $this->useCaseRunner,
            $form,
            $command,
            $this->updateAuthorUseCase,
            Yii::t('app', 'author.success.updated'),
        );

        return $result !== null;
    }

    public function deleteAuthor(int $id): bool
    {
        $command = new DeleteAuthorCommand($id);

        $result = $this->useCaseRunner->execute(
            $command,
            $this->deleteAuthorUseCase,
            Yii::t('app', 'author.success.deleted'),
            ['author_id' => $id],
        );

        return $result !== null;
    }
}