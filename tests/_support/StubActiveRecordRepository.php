<?php

declare(strict_types=1);

namespace tests\_support;

use app\domain\common\IdentifiableEntityInterface;
use app\domain\exceptions\DomainErrorCode;
use app\infrastructure\repositories\BaseActiveRecordRepository;
use yii\db\ActiveRecord;

final readonly class StubActiveRecordRepository extends BaseActiveRecordRepository
{
    public function testPersist(
        ActiveRecord $model,
        ?DomainErrorCode $duplicateError = null,
        ?DomainErrorCode $staleError = null,
    ): void {
        $this->persist($model, $duplicateError, $staleError);
    }

    public function testRegisterIdentity(IdentifiableEntityInterface $entity, ActiveRecord $ar): void
    {
        $this->registerIdentity($entity, $ar);
    }

    public function testDeleteEntity(
        IdentifiableEntityInterface $entity,
        string $arClass,
        DomainErrorCode $notFoundCode,
    ): void {
        $this->deleteEntity($entity, $arClass, $notFoundCode);
    }

    public function hasIdentity(IdentifiableEntityInterface $entity): bool
    {
        return isset($this->identityMap[$entity]);
    }
}
