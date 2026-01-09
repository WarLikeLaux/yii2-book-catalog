<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\domain\common\IdentifiableEntityInterface;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\OperationFailedException;
use app\domain\exceptions\StaleDataException;
use app\infrastructure\persistence\DatabaseErrorCode;
use WeakMap;
use yii\db\ActiveRecord;
use yii\db\IntegrityException;
use yii\db\StaleObjectException;

abstract readonly class BaseActiveRecordRepository
{
    /** @var WeakMap<IdentifiableEntityInterface, ActiveRecord> */
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
    protected function getArForEntity(IdentifiableEntityInterface $entity, string $arClass, DomainErrorCode $notFoundCode): ActiveRecord
    {
        if (isset($this->identityMap[$entity])) {
            return $this->identityMap[$entity]; // @phpstan-ignore return.type
        }

        $id = $this->getEntityId($entity);

        $ar = $this->getArById($id, $arClass, $notFoundCode);

        $this->identityMap[$entity] = $ar;

        return $ar;
    }

    protected function registerIdentity(IdentifiableEntityInterface $entity, ActiveRecord $ar): void
    {
        $this->identityMap[$entity] = $ar;
    }

    /**
     * @template T of ActiveRecord
     * @param class-string<T> $arClass
     */
    protected function deleteEntity(
        IdentifiableEntityInterface $entity,
        string $arClass,
        DomainErrorCode $notFoundCode,
    ): void {
        $id = $this->getEntityId($entity);

        $ar = $this->getArById($id, $arClass, $notFoundCode);

        if ($ar->delete() === false) {
            throw new OperationFailedException(DomainErrorCode::EntityDeleteFailed); // @codeCoverageIgnore
        }
    }

    /**
     * @throws OperationFailedException
     * @throws StaleDataException
     * @throws AlreadyExistsException
     * @throws IntegrityException
     */
    protected function persist(
        ActiveRecord $model,
        ?DomainErrorCode $duplicateError = null,
    ): void {
        try {
            if (!$model->save(false)) {
                throw new OperationFailedException(DomainErrorCode::EntityPersistFailed);
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

    protected function getEntityId(IdentifiableEntityInterface $entity): int
    {
        if ($entity->id === null) {
            throw new OperationFailedException(DomainErrorCode::EntityIdMissing); // @codeCoverageIgnore
        }

        return $entity->id;
    }
}
