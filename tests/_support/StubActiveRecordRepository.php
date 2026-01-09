<?php

declare(strict_types=1);

namespace tests\support;

use app\domain\exceptions\DomainErrorCode;
use app\infrastructure\repositories\BaseActiveRecordRepository;
use yii\db\ActiveRecord;

final readonly class StubActiveRecordRepository extends BaseActiveRecordRepository
{
    public function testPersist(
        ActiveRecord $model,
        ?DomainErrorCode $duplicateError = null,
    ): void {
        $this->persist($model, $duplicateError);
    }
}
