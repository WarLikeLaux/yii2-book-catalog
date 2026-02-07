<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\dto\BookEditViewModel;
use app\presentation\books\dto\BookViewViewModel;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\BookViewModelMapper;
use app\presentation\books\services\BookDtoUrlResolver;
use yii\web\NotFoundHttpException;

final readonly class BookItemViewFactory
{
    public function __construct(
        private BookQueryServiceInterface $finder,
        private AuthorQueryServiceInterface $authorQueryService,
        private BookDtoUrlResolver $urlResolver,
        private BookViewModelMapper $viewModelMapper,
    ) {
    }

    public function getCreateViewModel(BookForm|null $form = null): BookEditViewModel
    {
        return new BookEditViewModel(
            $form ?? $this->createForm(),
            $this->getAuthorsList(),
        );
    }

    public function createForm(): BookForm
    {
        return new BookForm();
    }

    public function getUpdateViewModel(int $id, BookForm|null $form = null): BookEditViewModel
    {
        $dto = $this->getBookById($id);

        return new BookEditViewModel(
            $form ?? $this->populateFormFromDto($dto),
            $this->getAuthorsList(),
            $this->viewModelMapper->map($this->urlResolver->resolveUrl($dto)),
        );
    }

    public function getBookViewModel(int $id): BookViewViewModel
    {
        return new BookViewViewModel(
            $this->viewModelMapper->map($this->getBookView($id)),
        );
    }

    public function getBookForUpdate(int $id): BookForm
    {
        return $this->populateFormFromDto($this->getBookById($id));
    }

    private function populateFormFromDto(BookReadDto $dto): BookForm
    {
        $form = new BookForm();
        $form->id = $dto->id;
        $form->title = $dto->title;
        $form->year = $dto->year;
        $form->isbn = $dto->isbn;
        $form->description = $dto->description;
        $form->authorIds = $dto->authorIds;
        $form->version = $dto->version;

        return $form;
    }

    public function getBookView(int $id): BookReadDto
    {
        $dto = $this->getBookById($id);

        return $this->urlResolver->resolveUrl($dto);
    }

    /**
     * @return array<int, string>
     */
    private function getAuthorsList(): array
    {
        $authors = $this->authorQueryService->findAllOrderedByFio();
        $map = [];

        foreach ($authors as $author) {
            $map[$author->id] = $author->fio;
        }

        return $map;
    }

    private function getBookById(int $id): BookReadDto
    {
        $dto = $this->finder->findById($id);

        if (!$dto instanceof BookReadDto) {
            throw new NotFoundHttpException();
        }

        return $dto;
    }
}
