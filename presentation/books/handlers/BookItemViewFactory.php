<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\dto\BookEditViewModel;
use app\presentation\books\dto\BookViewModel;
use app\presentation\books\dto\BookViewViewModel;
use app\presentation\books\forms\BookForm;
use app\presentation\services\FileUrlResolver;
use AutoMapper\AutoMapperInterface;
use yii\web\NotFoundHttpException;

final readonly class BookItemViewFactory
{
    public function __construct(
        private BookQueryServiceInterface $finder,
        private AuthorQueryServiceInterface $authorQueryService,
        private AutoMapperInterface $autoMapper,
        private FileUrlResolver $resolver,
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
        return new BookEditViewModel(
            $form ?? $this->getBookForUpdate($id),
            $this->getAuthorsList(),
            $this->mapToViewModel($this->getBookView($id)),
        );
    }

    public function getBookViewModel(int $id): BookViewViewModel
    {
        return new BookViewViewModel(
            $this->mapToViewModel($this->getBookView($id)),
        );
    }

    public function getBookForUpdate(int $id): BookForm
    {
        $dto = $this->getBookById($id);
        $form = new BookForm();

        /** @var BookForm */
        return $this->autoMapper->map($dto, $form);
    }

    public function getBookView(int $id): BookReadDto
    {
        $dto = $this->getBookById($id);

        return $this->withResolvedUrl($dto);
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

    private function withResolvedUrl(BookReadDto $dto): BookReadDto
    {
        return $dto->withCoverUrl($this->resolver->resolve($dto->coverUrl));
    }

    private function getBookById(int $id): BookReadDto
    {
        $dto = $this->finder->findById($id);

        if (!$dto instanceof BookReadDto) {
            throw new NotFoundHttpException();
        }

        return $dto;
    }

    private function mapToViewModel(BookReadDto $dto): BookViewModel
    {
        return new BookViewModel(
            $dto->id,
            $dto->title,
            $dto->year,
            $dto->description,
            $dto->isbn,
            $dto->authorNames,
            $dto->coverUrl,
            $dto->isPublished,
        );
    }
}
