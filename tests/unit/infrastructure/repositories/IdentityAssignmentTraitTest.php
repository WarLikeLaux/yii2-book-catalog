<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\repositories;

use app\domain\common\IdentifiableEntityInterface;
use app\infrastructure\repositories\IdentityAssignmentTrait;
use Codeception\Test\Unit;
use RuntimeException;

final class IdentityAssignmentTraitTest extends Unit
{
    private IdentifiableEntityInterface $entity;
    private ?object $traitObject = null;

    protected function _before(): void
    {
        $this->entity = new class implements IdentifiableEntityInterface {
            public ?int $id = null;

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
}
