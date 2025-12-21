<?php

declare(strict_types=1);

namespace app\application\books\queries;

use app\application\common\adapters\YiiDataProviderAdapter;
use app\interfaces\QueryResultInterface;
use app\models\Book;
use app\models\forms\BookSearchForm;
use app\repositories\BookReadRepository;
use Yii;
use yii\web\NotFoundHttpException;

final class BookQueryService
{
    public function __construct(
        private readonly BookReadRepository $repository
    ) {
    }

    public function getIndexProvider(): QueryResultInterface
    {
        $dataProvider = $this->repository->getIndexDataProvider();
        $dataProvider->setModels(array_map(
            fn(Book $model) => $this->mapToDto($model),
            $dataProvider->getModels()
        ));

        return new YiiDataProviderAdapter($dataProvider);
    }

    public function getById(int $id): BookReadDto
    {
        $book = $this->repository->findByIdWithAuthors($id);
        if (!$book) {
            throw new NotFoundHttpException(Yii::t('app', 'Book not found'));
        }

        return $this->mapToDto($book);
    }

    private function mapToDto(Book $book): BookReadDto
    {
        $authorNames = [];
        foreach ($book->authors as $author) {
            $authorNames[$author->id] = $author->fio;
        }

        return new BookReadDto(
            id: $book->id,
            title: $book->title,
            year: $book->year,
            description: $book->description,
            isbn: $book->isbn,
            authorIds: array_keys($authorNames),
            authorNames: $authorNames,
            coverUrl: $book->cover_url
        );
    }

    public function search(array $params): BookSearchPageData
    {
        $form = new BookSearchForm();
        $form->load($params);

        $dataProvider = $this->repository->search(
            $form->globalSearch,
            pageSize: 9
        );

        $dataProvider->setModels(array_map(
            fn(Book $model) => $this->mapToDto($model),
            $dataProvider->getModels()
        ));

        return new BookSearchPageData($form, new YiiDataProviderAdapter($dataProvider));
    }
}
