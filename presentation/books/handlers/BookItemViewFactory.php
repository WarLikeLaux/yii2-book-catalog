<?php

declare(strict_types=1);

namespace app\presentation\books\handlers;

use app\application\books\queries\BookReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookFinderInterface;
use app\presentation\books\dto\BookEditViewModel;
use app\presentation\books\dto\BookViewViewModel;
use app\presentation\books\forms\BookForm;
use app\presentation\services\FileUrlResolver;
use AutoMapper\AutoMapperInterface;
use LogicException;
use yii\web\NotFoundHttpException;

final readonly class BookItemViewFactory
{
    public function __construct(
        private BookFinderInterface $finder,
        private AuthorQueryServiceInterface $authorQueryService,
        private AutoMapperInterface $autoMapper,
        private FileUrlResolver $resolver,
    ) {
    }

    public function getCreateViewModel(BookForm|null $form = null): BookEditViewModel
    {
        return new BookEditViewModel(
            $form ?? new BookForm(),
            $this->getAuthorsList(),
        );
    }

    public function getUpdateViewModel(int $id, BookForm|null $form = null): BookEditViewModel
    {
        return new BookEditViewModel(
            $form ?? $this->getBookForUpdate($id),
            $this->getAuthorsList(),
            $this->getBookView($id),
        );
    }

    public function getBookViewModel(int $id): BookViewViewModel
    {
        return new BookViewViewModel(
            $this->getBookView($id),
        );
    }

    public function getBookForUpdate(int $id): BookForm
    {
        $dto = $this->getBookById($id);
        $form = $this->autoMapper->map($dto, BookForm::class);

        if (!$form instanceof BookForm) {
            throw new LogicException(sprintf(
                'AutoMapper не смог преобразовать %s в %s в getBookForUpdate: получен %s',
                BookReadDto::class,
                BookForm::class,
                get_debug_type($form),
            ));
        }

        return $form;
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
        return new BookReadDto(
            $dto->id,
            $dto->title,
            $dto->year,
            $dto->description,
            $dto->isbn,
            $dto->authorIds,
            $dto->authorNames,
            $this->resolver->resolve($dto->coverUrl),
            $dto->isPublished,
            $dto->version,
        );
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
