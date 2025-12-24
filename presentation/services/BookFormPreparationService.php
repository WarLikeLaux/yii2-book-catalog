<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\authors\queries\AuthorQueryService;
use app\application\books\commands\DeleteBookCommand;
use app\application\books\queries\BookQueryService;
use app\application\books\queries\BookReadDto;
use app\application\books\usecases\CreateBookUseCase;
use app\application\books\usecases\DeleteBookUseCase;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\UseCaseExecutor;
use app\presentation\adapters\PagedResultDataProviderFactory;
use app\presentation\dto\BookCreateFormResult;
use app\presentation\dto\BookUpdateFormResult;
use app\presentation\forms\BookForm;
use app\presentation\mappers\BookFormMapper;
use Yii;
use yii\web\Request;
use yii\web\Response;
use yii\widgets\ActiveForm;

final class BookFormPreparationService
{
    public function __construct(
        private readonly BookFormMapper $bookFormMapper,
        private readonly BookQueryService $bookQueryService,
        private readonly AuthorQueryService $authorQueryService,
        private readonly CreateBookUseCase $createBookUseCase,
        private readonly UpdateBookUseCase $updateBookUseCase,
        private readonly DeleteBookUseCase $deleteBookUseCase,
        private readonly UseCaseExecutor $useCaseExecutor,
        private readonly PagedResultDataProviderFactory $dataProviderFactory
    ) {
    }

    public function prepareForUpdate(BookReadDto $dto): BookForm
    {
        return $this->bookFormMapper->toForm($dto);
    }

    public function prepareUpdateForm(int $id): BookForm
    {
        $dto = $this->bookQueryService->getById($id);
        return $this->bookFormMapper->toForm($dto);
    }

    public function prepareUpdateViewData(int $id): array
    {
        $bookDto = $this->bookQueryService->getById($id);
        $form = $this->bookFormMapper->toForm($bookDto);
        $authors = $this->authorQueryService->getAuthorsMap();

        return [
            'model' => $form,
            'book' => $bookDto,
            'authors' => $authors,
        ];
    }

    public function prepareCreateViewData(): array
    {
        $form = new BookForm();
        $authors = $this->authorQueryService->getAuthorsMap();

        return [
            'model' => $form,
            'authors' => $authors,
        ];
    }

    public function prepareIndexViewData(Request $request): array
    {
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = max(1, (int)$request->get('pageSize', 20));
        $queryResult = $this->bookQueryService->getIndexProvider($page, $pageSize);
        $dataProvider = $this->dataProviderFactory->create($queryResult);

        return [
            'dataProvider' => $dataProvider,
        ];
    }

    public function prepareViewViewData(int $id): array
    {
        $book = $this->bookQueryService->getById($id);

        return [
            'book' => $book,
        ];
    }

    public function processCreateRequest(Request $request, Response $response): BookCreateFormResult
    {
        $viewData = $this->prepareCreateViewData();
        $form = $viewData['model'];

        if (!$form->loadFromRequest($request)) {
            return new BookCreateFormResult($form, $viewData, false);
        }

        if ($request->isAjax) {
            $response->format = Response::FORMAT_JSON;
            $ajaxValidation = ActiveForm::validate($form);
            return new BookCreateFormResult($form, $viewData, false, null, $ajaxValidation);
        }

        if (!$form->validate()) {
            return new BookCreateFormResult($form, $viewData, false);
        }

        $command = $this->bookFormMapper->toCreateCommand($form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->createBookUseCase->execute($command),
            Yii::t('app', 'Book has been created')
        );

        if ($success) {
            return new BookCreateFormResult($form, $viewData, true, ['index']);
        }

        return new BookCreateFormResult($form, $viewData, false);
    }

    public function processUpdateRequest(int $id, Request $request, Response $response): BookUpdateFormResult
    {
        $viewData = $this->prepareUpdateViewData($id);
        $form = $viewData['model'];

        if (!$form->loadFromRequest($request)) {
            return new BookUpdateFormResult($form, $viewData, false);
        }

        if ($request->isAjax) {
            $response->format = Response::FORMAT_JSON;
            $ajaxValidation = ActiveForm::validate($form);
            return new BookUpdateFormResult($form, $viewData, false, null, $ajaxValidation);
        }

        if (!$form->validate()) {
            return new BookUpdateFormResult($form, $viewData, false);
        }

        $command = $this->bookFormMapper->toUpdateCommand($id, $form);
        $success = $this->useCaseExecutor->execute(
            fn() => $this->updateBookUseCase->execute($command),
            Yii::t('app', 'Book has been updated'),
            ['book_id' => $id]
        );

        if ($success) {
            return new BookUpdateFormResult($form, $viewData, true, ['view', 'id' => $id]);
        }

        return new BookUpdateFormResult($form, $viewData, false);
    }

    public function processDeleteRequest(int $id): void
    {
        $command = new DeleteBookCommand($id);
        $this->useCaseExecutor->execute(
            fn() => $this->deleteBookUseCase->execute($command),
            Yii::t('app', 'Book has been deleted'),
            ['book_id' => $id]
        );
    }
}
