<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\authors\queries\AuthorQueryService;
use app\application\authors\queries\AuthorReadDto;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\models\forms\AuthorForm;
use app\presentation\dto\AuthorCreateFormResult;
use app\presentation\dto\AuthorUpdateFormResult;
use app\presentation\mappers\AuthorFormMapper;
use app\presentation\UseCaseExecutor;
use Yii;
use yii\web\Request;

final class AuthorFormPreparationService
{
    public function __construct(
        private readonly AuthorFormMapper $authorFormMapper,
        private readonly AuthorQueryService $authorQueryService,
        private readonly CreateAuthorUseCase $createAuthorUseCase,
        private readonly UpdateAuthorUseCase $updateAuthorUseCase,
        private readonly UseCaseExecutor $useCaseExecutor
    ) {
    }

    public function prepareForUpdate(AuthorReadDto $dto): AuthorForm
    {
        return $this->authorFormMapper->toForm($dto);
    }

    public function prepareUpdateForm(int $id): AuthorForm
    {
        $dto = $this->authorQueryService->getById($id);
        return $this->authorFormMapper->toForm($dto);
    }

    public function processCreateRequest(Request $request): AuthorCreateFormResult
    {
        $form = new AuthorForm();

        if (!$form->load($request->post()) || !$form->validate()) {
            return new AuthorCreateFormResult($form, false);
        }

        $command = $this->authorFormMapper->toCreateCommand($form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->createAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been created')
        );

        if ($success) {
            return new AuthorCreateFormResult($form, true, ['index']);
        }

        return new AuthorCreateFormResult($form, false);
    }

    public function processUpdateRequest(int $id, Request $request): AuthorUpdateFormResult
    {
        $form = $this->prepareUpdateForm($id);

        if (!$form->load($request->post()) || !$form->validate()) {
            return new AuthorUpdateFormResult($form, false);
        }

        $command = $this->authorFormMapper->toUpdateCommand($id, $form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->updateAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been updated'),
            ['author_id' => $id]
        );

        if ($success) {
            return new AuthorUpdateFormResult($form, true, ['index']);
        }

        return new AuthorUpdateFormResult($form, false);
    }
}
