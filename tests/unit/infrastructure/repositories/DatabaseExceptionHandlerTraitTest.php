<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories;

use app\infrastructure\repositories\DatabaseExceptionHandlerTrait;
use Codeception\Test\Unit;
use yii\db\IntegrityException;

final class DatabaseExceptionHandlerTraitTest extends Unit
{
    protected object $testerObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testerObject = new class {
            use DatabaseExceptionHandlerTrait;

            public function testIsDuplicate(IntegrityException $e): bool
            {
                return $this->isDuplicateError($e);
            }
        };
    }

    public function testIsDuplicateErrorReturnsTrueForDuplicateCode(): void
    {
        $exception = new IntegrityException('Duplicate entry', ['SQLSTATE[23000]', 1062, 'Duplicate entry']);

        $this->assertTrue($this->testerObject->testIsDuplicate($exception));
    }

    public function testIsDuplicateErrorReturnsFalseForOtherErrors(): void
    {
        $exception = new IntegrityException('Some other error', ['SQLSTATE[23000]', 1234, 'Some other error']);

        $this->assertFalse($this->testerObject->testIsDuplicate($exception));
    }

    public function testIsDuplicateErrorHandlesMissingErrorInfo(): void
    {
        $exception = new IntegrityException('Generic integrity error', []);

        $this->assertFalse($this->testerObject->testIsDuplicate($exception));
    }
}
