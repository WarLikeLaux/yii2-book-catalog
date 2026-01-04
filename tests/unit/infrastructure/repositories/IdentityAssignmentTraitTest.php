<?php

// phpcs:ignoreFile
// NOTE: Файл игнорируется из-за фатальной ошибки в Slevomat Sniffs (Undefined array key "scope_closer")

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\infrastructure\repositories\IdentityAssignmentTrait;
use Codeception\Test\Unit;
use RuntimeException;

final class IdentityAssignmentTraitTest extends Unit
{
    private object $entity;
    private $traitObject;

    protected function _before(): void
    {
        $this->entity = new class {
            private ?int $id = null;
            public function getId(): ?int
            {
                return $this->id;
            }
        };

        $this->traitObject = new class {
            use IdentityAssignmentTrait {
                assignId as public;
            }
        };
    }

    public function testAssignIdSuccess(): void
    {
        $this->traitObject->assignId($this->entity, 42);
        $this->assertSame(42, $this->entity->getId());
    }

    public function testAssignSameIdSuccess(): void
    {
        $this->traitObject->assignId($this->entity, 42);
        $this->traitObject->assignId($this->entity, 42);

        $this->assertSame(42, $this->entity->getId());
    }

    public function testAssignIdThrowsExceptionOnOverwrite(): void
    {
        $this->traitObject->assignId($this->entity, 42);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot overwrite ID for');
        $this->expectExceptionMessage('current: 42, new: 99');

        $this->traitObject->assignId($this->entity, 99);
    }

    public function testAssignIdHandlesNonScalarCurrentValueInException(): void
    {
        $entityWithObject = new class {
            private $id;
            public function __construct()
            {
                $this->id = new \stdClass();
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('current: object');

        $this->traitObject->assignId($entityWithObject, 42);
    }
}