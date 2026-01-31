<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\dto\AuthorEditViewModel;
use app\presentation\authors\dto\AuthorViewViewModel;
use app\presentation\authors\forms\AuthorForm;
use AutoMapper\AutoMapperInterface;
use LogicException;
use yii\web\NotFoundHttpException;

final readonly class AuthorItemViewFactory
{
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
        private AutoMapperInterface $autoMapper,
    ) {
    }

    public function getCreateViewModel(AuthorForm|null $form = null): AuthorEditViewModel
    {
        return new AuthorEditViewModel(
            $form ?? $this->createForm(),
        );
    }

    public function createForm(): AuthorForm
    {
        return new AuthorForm($this->queryService);
    }

    public function getUpdateViewModel(int $id, AuthorForm|null $form = null): AuthorEditViewModel
    {
        return new AuthorEditViewModel(
            $form ?? $this->getAuthorForUpdate($id),
            $this->getAuthorView($id),
        );
    }

    public function getAuthorViewModel(int $id): AuthorViewViewModel
    {
        return new AuthorViewViewModel(
            $this->getAuthorView($id),
        );
    }

    public function getAuthorForUpdate(int $id): AuthorForm
    {
        $dto = $this->queryService->findById($id) ?? throw new NotFoundHttpException();

        $form = new AuthorForm($this->queryService);
        $form = $this->autoMapper->map($dto, $form);

        if (!$form instanceof AuthorForm) {
            throw new LogicException(sprintf(
                'AutoMapper returned unexpected type: expected %s, got %s',
                AuthorForm::class,
                get_debug_type($form),
            ));
        }

        return $form;
    }

    public function getAuthorView(int $id): AuthorReadDto
    {
        return $this->queryService->findById($id) ?? throw new NotFoundHttpException();
    }
}
