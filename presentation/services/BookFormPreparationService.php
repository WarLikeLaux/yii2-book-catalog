<?php

declare(strict_types=1);

namespace app\presentation\services;

use app\application\authors\queries\AuthorQueryService;
use app\application\books\queries\BookQueryService;
use app\application\books\queries\BookReadDto;
use app\models\forms\BookForm;
use app\presentation\mappers\BookFormMapper;

final class BookFormPreparationService
{
    public function __construct(
        private readonly BookFormMapper $bookFormMapper,
        private readonly BookQueryService $bookQueryService,
        private readonly AuthorQueryService $authorQueryService
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
        $form = $this->prepareUpdateForm($id);
        $book = $this->bookQueryService->getById($id);
        $authors = $this->authorQueryService->getAuthorsMap();

        return [
            'model' => $form,
            'book' => $book,
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
}
