<?php

declare(strict_types=1);

namespace app\application\authors\queries;

use app\models\Author;
use app\models\forms\AuthorSearchForm;
use app\repositories\AuthorReadRepository;
use Yii;
use yii\data\DataProviderInterface;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

final class AuthorQueryService
{
    public function __construct(
        private readonly AuthorReadRepository $repository
    ) {
    }

    public function getIndexProvider(): DataProviderInterface
    {
        $dataProvider = $this->repository->getIndexDataProvider();
        $dataProvider->setModels(array_map(
            fn(Author $model) => $this->mapToDto($model),
            $dataProvider->getModels()
        ));

        return $dataProvider;
    }

    public function getAuthorsMap(): array
    {
        return ArrayHelper::map($this->repository->findAllOrderedByFio()->all(), 'id', 'fio');
    }

    public function getById(int $id): AuthorReadDto
    {
        $author = $this->repository->findById($id);
        if (!$author) {
            throw new NotFoundHttpException(Yii::t('app', 'Author not found'));
        }

        return $this->mapToDto($author);
    }

    private function mapToDto(Author $author): AuthorReadDto
    {
        return new AuthorReadDto(
            id: $author->id,
            fio: $author->fio
        );
    }

    public function search(array $params): AuthorSearchResponse
    {
        $searchForm = new AuthorSearchForm();
        $searchForm->load($params);

        if (!$searchForm->validate()) {
            return new AuthorSearchResponse([], 0, 1, 20);
        }

        $result = $this->repository->search(
            $searchForm->q,
            $searchForm->page,
            $searchForm->pageSize
        );

        return new AuthorSearchResponse(
            items: array_map(fn(Author $author) => $this->mapToDto($author), $result['items']),
            total: $result['total'],
            page: $searchForm->page,
            pageSize: $searchForm->pageSize
        );
    }
}
