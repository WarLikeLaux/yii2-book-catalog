<?php

declare(strict_types=1);

namespace app\presentation\authors\handlers;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\common\adapters\PagedResultDataProviderFactory;
use AutoMapper\AutoMapperInterface;
use yii\data\DataProviderInterface;
use yii\web\NotFoundHttpException;

final readonly class AuthorViewDataFactory
{
    /**
     * Initialize the factory with its dependencies for producing author view data.
     *
     * @param AuthorQueryServiceInterface $queryService Service used to fetch author DTOs.
     * @param AutoMapperInterface $autoMapper Mapper used to convert DTOs to presentation forms (e.g., AuthorForm).
     * @param PagedResultDataProviderFactory $dataProviderFactory Factory that creates paginated data providers from query results.
     */
    public function __construct(
        private AuthorQueryServiceInterface $queryService,
        private AutoMapperInterface $autoMapper,
        private PagedResultDataProviderFactory $dataProviderFactory,
    ) {
    }

    /**
     * Creates a data provider for the authors index using the given pagination.
     *
     * @param int $page The page number.
     * @param int $pageSize The number of items per page.
     * @return DataProviderInterface A data provider containing paginated author results.
     */
    public function getIndexDataProvider(int $page, int $pageSize): DataProviderInterface
    {
        $queryResult = $this->queryService->search('', $page, $pageSize);
        return $this->dataProviderFactory->create($queryResult);
    }

    /**
     * Create an AuthorForm populated with data for the author identified by $id.
     *
     * @param int $id The author's identifier.
     * @return AuthorForm The form populated with the author's data.
     * @throws \yii\web\NotFoundHttpException If no author with the given id exists.
     */
    public function getAuthorForUpdate(int $id): AuthorForm
    {
        $dto = $this->queryService->findById($id)
            ?? throw new NotFoundHttpException();

        /** @var AuthorForm */
        return $this->autoMapper->map($dto, AuthorForm::class);
    }

    /**
     * Fetches the author read DTO for the given identifier.
     *
     * @param int $id The author's identifier.
     * @return AuthorReadDto The AuthorReadDto for the requested author.
     * @throws \yii\web\NotFoundHttpException If no author exists with the given identifier.
     */
    public function getAuthorView(int $id): AuthorReadDto
    {
        return $this->queryService->findById($id)
            ?? throw new NotFoundHttpException();
    }
}