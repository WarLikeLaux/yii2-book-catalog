<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\domain\common\IdentifiableEntityInterface;
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
    /** @var WeakMap<IdentifiableEntityInterface, ActiveRecord> */
    protected WeakMap $identityMap;

    /**
     * Initialize the repository and create an empty identity map for caching entity-to-ActiveRecord mappings.
     */
    public function __construct()
    {
        $this->identityMap = new WeakMap();
    }

    /**
     * Retrieve the ActiveRecord instance matching the given identifier.
     *
     * @template T of ActiveRecord
     * @param int|string $id The identifier of the record.
     * @param class-string<T> $arClass The ActiveRecord class to query.
     * @param DomainErrorCode $notFoundCode Error code used when the entity is not found.
     * @return T The ActiveRecord instance for the specified id.
     * @throws EntityNotFoundException If no record exists for the given id.
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
         * Resolve the ActiveRecord instance for a given domain entity, reusing a cached instance from the identity map when available.
         *
         * @template T of ActiveRecord
         * @param IdentifiableEntityInterface $entity The domain entity whose ActiveRecord is required.
         * @param class-string<T> $arClass The ActiveRecord class to retrieve.
         * @param DomainErrorCode $notFoundCode Domain error code used if the ActiveRecord cannot be found.
         * @return T The ActiveRecord instance corresponding to the provided entity.
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

    /**
     * Registers an association between a domain entity and its ActiveRecord instance in the repository identity map.
     *
     * @param IdentifiableEntityInterface $entity The domain entity to register.
     * @param ActiveRecord $ar The corresponding ActiveRecord instance.
     */
    protected function registerIdentity(IdentifiableEntityInterface $entity, ActiveRecord $ar): void
    {
        $this->identityMap[$entity] = $ar;
    }

    /**
     * Deletes the ActiveRecord corresponding to the given domain entity.
     *
     * @template T of ActiveRecord
     * @param IdentifiableEntityInterface $entity The domain entity whose mapped ActiveRecord should be deleted.
     * @param class-string<T> $arClass The ActiveRecord class to use when loading the record.
     * @param DomainErrorCode $notFoundCode Error code used if the ActiveRecord cannot be found.
     * @param string $errorMessage Error message for the thrown RuntimeException when deletion fails.
     *
     * @throws EntityNotFoundException If no ActiveRecord exists for the entity's ID.
     * @throws RuntimeException If the ActiveRecord's delete() returns false.
     */
    protected function deleteEntity(
        IdentifiableEntityInterface $entity,
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
         * Persist the given ActiveRecord to the database and convert common persistence failures into domain-specific exceptions.
         *
         * Attempts to save the model without validation. If the save fails due to model errors, throws a RuntimeException
         * whose message is the first model error or the provided $errorMessage. If an optimistic locking conflict occurs,
         * throws StaleDataException. If a database integrity error indicates a duplicate key, throws AlreadyExistsException;
         * when $duplicateError is provided it will be used as the domain error code with HTTP 409. Other IntegrityException
         * instances are rethrown unchanged.
         *
         * @param ActiveRecord $model The ActiveRecord instance to persist.
         * @param DomainErrorCode|null $duplicateError Optional domain error code to use when a duplicate constraint is detected.
         * @param string $errorMessage Default error message used when the model has no explicit errors.
         *
         * @throws RuntimeException If the model save fails and no specific database integrity/locking exception occurs.
         * @throws StaleDataException If an optimistic locking (stale object) conflict is detected.
         * @throws AlreadyExistsException If a duplicate database constraint is detected.
         * @throws IntegrityException For integrity errors that are not identified as duplicate-key violations.
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

    /**
     * Determine whether the given database integrity exception corresponds to a duplicate-key/unique constraint violation.
     *
     * @param IntegrityException $e The integrity exception whose error info will be inspected.
     * @return bool `true` if the exception represents a duplicate error (by driver code or SQL state), `false` otherwise.
     */
    private function isDuplicateError(IntegrityException $e): bool
    {
        $driverCode = $e->errorInfo[1] ?? null;
        $sqlState = $e->errorInfo[0] ?? null;

        return DatabaseErrorCode::isDuplicate($driverCode)
            || DatabaseErrorCode::isDuplicate($sqlState);
    }

    /**
     * Retrieve the integer identifier of the given entity.
     *
     * @param IdentifiableEntityInterface $entity The entity whose ID will be returned.
     * @return int The entity's ID.
     * @throws RuntimeException If the entity has no ID.
     */
    protected function getEntityId(IdentifiableEntityInterface $entity): int
    {
        if ($entity->id === null) {
            throw new RuntimeException('Entity has no ID.'); // @codeCoverageIgnore
        }

        return $entity->id;
    }
}