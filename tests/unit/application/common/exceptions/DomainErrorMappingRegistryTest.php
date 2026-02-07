<?php

declare(strict_types=1);

namespace tests\unit\application\common\exceptions;

use app\application\common\exceptions\AlreadyExistsException;
use app\application\common\exceptions\BusinessRuleException;
use app\application\common\exceptions\DomainErrorMappingRegistry;
use app\application\common\exceptions\EntityNotFoundException;
use app\application\common\exceptions\OperationFailedException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ErrorType;
use Codeception\Test\Unit;

final class DomainErrorMappingRegistryTest extends Unit
{
    public function testFromEnumRegistersAllCases(): void
    {
        $registry = DomainErrorMappingRegistry::fromEnum();

        foreach (DomainErrorCode::cases() as $case) {
            $this->assertNotNull(
                $registry->getMapping($case),
                sprintf('Mapping for %s is missing', $case->name),
            );
        }
    }

    public function testFromEnumPreservesExistingMappings(): void
    {
        $registry = DomainErrorMappingRegistry::fromEnum();

        $this->assertMapping($registry, DomainErrorCode::BookIsbnExists, AlreadyExistsException::class, 'isbn');
        $this->assertMapping($registry, DomainErrorCode::BookAuthorsNotFound, EntityNotFoundException::class, 'authorIds');
        $this->assertMapping($registry, DomainErrorCode::BookTitleEmpty, OperationFailedException::class, 'title');
        $this->assertMapping($registry, DomainErrorCode::BookNotFound, EntityNotFoundException::class, null);
        $this->assertMapping($registry, DomainErrorCode::AuthorFioExists, AlreadyExistsException::class, 'fio');
        $this->assertMapping($registry, DomainErrorCode::AuthorUpdateFailed, OperationFailedException::class, 'fio');
        $this->assertMapping($registry, DomainErrorCode::AuthorNotFound, EntityNotFoundException::class, null);
        $this->assertMapping($registry, DomainErrorCode::SubscriptionAlreadySubscribed, AlreadyExistsException::class, null);
        $this->assertMapping($registry, DomainErrorCode::SubscriptionCreateFailed, OperationFailedException::class, null);
    }

    public function testFromEnumMapsBusinessRuleCases(): void
    {
        $registry = DomainErrorMappingRegistry::fromEnum();

        $this->assertMapping($registry, DomainErrorCode::BookPublishWithoutAuthors, BusinessRuleException::class, null);
        $this->assertMapping($registry, DomainErrorCode::BookPublishWithoutCover, BusinessRuleException::class, null);
        $this->assertMapping($registry, DomainErrorCode::BookPublishShortDescription, BusinessRuleException::class, null);
        $this->assertMapping($registry, DomainErrorCode::AuthInvalidCredentials, BusinessRuleException::class, null);
        $this->assertMapping($registry, DomainErrorCode::BookIsbnChangePublished, BusinessRuleException::class, 'isbn');
    }

    public function testManualRegisterOverridesMapping(): void
    {
        $registry = DomainErrorMappingRegistry::fromEnum();

        $registry->register(DomainErrorCode::BookNotFound, AlreadyExistsException::class, 'custom');

        $this->assertMapping($registry, DomainErrorCode::BookNotFound, AlreadyExistsException::class, 'custom');
    }

    public function testGetMappingReturnsNullForUnregistered(): void
    {
        $registry = new DomainErrorMappingRegistry();

        $this->assertNull($registry->getMapping(DomainErrorCode::BookNotFound));
    }

    /**
     * @param class-string $expectedClass
     */
    private function assertMapping(
        DomainErrorMappingRegistry $registry,
        DomainErrorCode $code,
        string $expectedClass,
        ?string $expectedField,
    ): void {
        $mapping = $registry->getMapping($code);
        $this->assertNotNull($mapping, sprintf('Mapping for %s should exist', $code->name));
        $this->assertSame($expectedClass, $mapping[0], sprintf('Exception class for %s', $code->name));
        $this->assertSame($expectedField, $mapping[1], sprintf('Field for %s', $code->name));
    }

    public function testResolveExceptionClassReturnsCorrectClassForAllTypes(): void
    {
        $cases = [
            [ErrorType::NotFound, EntityNotFoundException::class],
            [ErrorType::AlreadyExists, AlreadyExistsException::class],
            [ErrorType::OperationFailed, OperationFailedException::class],
            [ErrorType::BusinessRule, BusinessRuleException::class],
        ];

        foreach ($cases as [$type, $expectedClass]) {
            $this->assertSame(
                $expectedClass,
                DomainErrorMappingRegistry::resolveExceptionClass($type),
                sprintf('resolveExceptionClass for %s', $type->name),
            );
        }
    }
}
