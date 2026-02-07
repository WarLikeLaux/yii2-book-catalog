<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ErrorMapping;
use ReflectionEnum;

final class DomainErrorMappingRegistry
{
    private const array TYPE_MAP = [
        'NotFound' => EntityNotFoundException::class,
        'AlreadyExists' => AlreadyExistsException::class,
        'OperationFailed' => OperationFailedException::class,
        'BusinessRule' => BusinessRuleException::class,
    ];

    /**
     * @var array<string, array{class-string<ApplicationException>, string|null}>
     */
    private array $mappings = [];

    public static function fromEnum(): self
    {
        $registry = new self();
        /** @var ReflectionEnum<DomainErrorCode> $reflection */
        $reflection = new ReflectionEnum(DomainErrorCode::class);

        foreach ($reflection->getCases() as $case) {
            $attrs = $case->getAttributes(ErrorMapping::class);

            if ($attrs === []) {
                continue; // @codeCoverageIgnore
            }

            $mapping = $attrs[0]->newInstance();
            $errorCode = $case->getValue();
            assert($errorCode instanceof DomainErrorCode);

            $registry->register(
                $errorCode,
                self::TYPE_MAP[$mapping->type->name],
                $mapping->field,
            );
        }

        return $registry;
    }

    /**
     * @param class-string<ApplicationException> $exceptionClass
     */
    public function register(
        DomainErrorCode $errorCode,
        string $exceptionClass,
        ?string $field = null,
    ): void {
        $this->mappings[$errorCode->value] = [$exceptionClass, $field];
    }

    /**
     * @return array{class-string<ApplicationException>, string|null}|null
     */
    public function getMapping(DomainErrorCode $errorCode): ?array
    {
        return $this->mappings[$errorCode->value] ?? null;
    }
}
