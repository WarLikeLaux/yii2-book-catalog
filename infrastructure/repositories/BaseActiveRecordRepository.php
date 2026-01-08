<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\StaleDataException;
use app\infrastructure\persistence\DatabaseErrorCode;
use RuntimeException;
use WeakMap;
use yii\db\ActiveRecord;
use yii\db\IntegrityException;
use yii\db\StaleObjectException;

abstract readonly class BaseActiveRecordRepository
{
    /** @var WeakMap<object, ActiveRecord> */
    protected WeakMap $identityMap;

    public function __construct()
    {
        $this->identityMap = new WeakMap();
    }

    /**
     * @template T of ActiveRecord
     * @param class-string<T> $arClass
     * @return T
     * @throws EntityNotFoundException
     */
    protected function getArById(int|string $id, string $arClass, DomainErrorCode $notFoundCode): ActiveRecord
    {
        /** @var T|null $ar */
        $ar = $arClass::findOne($id);

        if ($ar === null) {
            throw new EntityNotFoundException($notFoundCode);
        }

        return $ar;
    }

    /**
     * @template T of ActiveRecord
     * @param class-string<T> $arClass
     * @return T
     */
    protected function getArForEntity(object $entity, string $arClass, DomainErrorCode $notFoundCode): ActiveRecord
    {
        if (isset($this->identityMap[$entity])) {
            return $this->identityMap[$entity]; // @phpstan-ignore return.type
        }

        $id = $this->getEntityId($entity);

        $ar = $this->getArById($id, $arClass, $notFoundCode);

        $this->identityMap[$entity] = $ar;

        return $ar;
    }

    protected function registerIdentity(object $entity, ActiveRecord $ar): void
    {
        $this->identityMap[$entity] = $ar;
    }

    /**
     * @template T of ActiveRecord
     * @param class-string<T> $arClass
     */
    protected function deleteEntity(
        object $entity,
        string $arClass,
        DomainErrorCode $notFoundCode,
        string $errorMessage = 'entity.error.delete_failed',
    ): void {
        $id = $this->getEntityId($entity);

        $ar = $this->getArById($id, $arClass, $notFoundCode);

        if ($ar->delete() === false) {
            throw new RuntimeException($errorMessage); // @codeCoverageIgnore
        }
    }

    /**
     * @throws RuntimeException
     * @throws StaleDataException
     * @throws AlreadyExistsException
     * @throws IntegrityException
     */
    protected function persist(
        ActiveRecord $model,
        ?DomainErrorCode $duplicateError = null,
        string $errorMessage = 'entity.error.save_failed',
    ): void {
        try {
            if (!$model->save(false)) {
                $errors = $model->getFirstErrors();
                $message = $errors !== [] ? array_shift($errors) : $errorMessage;
                throw new RuntimeException($message);
            }
        } catch (StaleObjectException) {
            throw new StaleDataException();
        } catch (IntegrityException $e) {
            if ($this->isDuplicateError($e)) {
                if ($duplicateError instanceof DomainErrorCode) {
                    throw new AlreadyExistsException($duplicateError, 409, $e);
                }

                throw new AlreadyExistsException(previous: $e);
            }

            throw $e;
        }
    }

    private function isDuplicateError(IntegrityException $e): bool
    {
        $driverCode = $e->errorInfo[1] ?? null;
        $sqlState = $e->errorInfo[0] ?? null;

        return DatabaseErrorCode::isDuplicate($driverCode)
            || DatabaseErrorCode::isDuplicate($sqlState);
    }

    protected function getEntityId(object $entity): int|string
    {
        return $entity->id ?? $entity->getId(); // @phpstan-ignore-line
    }
}
