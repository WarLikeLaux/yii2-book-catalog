<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\queries\AuthorQueryService;
use app\application\authors\queries\AuthorReadDto;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\models\forms\AuthorForm;
use app\presentation\adapters\PagedResultDataProviderFactory;
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
        private readonly DeleteAuthorUseCase $deleteAuthorUseCase,
        private readonly UseCaseExecutor $useCaseExecutor,
        private readonly PagedResultDataProviderFactory $dataProviderFactory
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

    public function prepareCreateViewData(): array
    {
        $form = new AuthorForm();

        return [
            'model' => $form,
        ];
    }

    public function prepareIndexViewData(Request $request): array
    {
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = max(1, (int)$request->get('pageSize', 20));
        $queryResult = $this->authorQueryService->getIndexProvider($page, $pageSize);
        $dataProvider = $this->dataProviderFactory->create($queryResult);

        return [
            'dataProvider' => $dataProvider,
        ];
    }

    public function prepareViewViewData(int $id): array
    {
        $author = $this->authorQueryService->getById($id);

        return [
            'author' => $author,
        ];
    }

    public function prepareUpdateViewData(int $id): array
    {
        $form = $this->prepareUpdateForm($id);

        return [
            'model' => $form,
        ];
    }

    public function processCreateRequest(Request $request): AuthorCreateFormResult
    {
        $viewData = $this->prepareCreateViewData();
        $form = $viewData['model'];

        if (!$form->load($request->post()) || !$form->validate()) {
            return new AuthorCreateFormResult($form, $viewData, false);
        }

        $command = $this->authorFormMapper->toCreateCommand($form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->createAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been created')
        );

        if ($success) {
            return new AuthorCreateFormResult($form, $viewData, true, ['index']);
        }

        return new AuthorCreateFormResult($form, $viewData, false);
    }

    public function processUpdateRequest(int $id, Request $request): AuthorUpdateFormResult
    {
        $viewData = $this->prepareUpdateViewData($id);
        $form = $viewData['model'];

        if (!$form->load($request->post()) || !$form->validate()) {
            return new AuthorUpdateFormResult($form, $viewData, false);
        }

        $command = $this->authorFormMapper->toUpdateCommand($id, $form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->updateAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been updated'),
            ['author_id' => $id]
        );

        if ($success) {
            return new AuthorUpdateFormResult($form, $viewData, true, ['index']);
        }

        return new AuthorUpdateFormResult($form, $viewData, false);
    }

    public function processDeleteRequest(int $id): void
    {
        $command = new DeleteAuthorCommand($id);
        $this->useCaseExecutor->execute(
            fn() => $this->deleteAuthorUseCase->execute($command),
            Yii::t('app', 'Author has been deleted'),
            ['author_id' => $id]
        );
    }
}
