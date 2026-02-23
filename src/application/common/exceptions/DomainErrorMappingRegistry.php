<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ErrorMapping;
use app\domain\exceptions\ErrorType;
use ReflectionEnum;
use RuntimeException;

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

            $exceptionClass = self::resolveExceptionClass($mapping->type);

            $registry->register(
                $errorCode,
                $exceptionClass,
                $mapping->field,
            );
        }

        return $registry;
    }

    /**
     * @return class-string<ApplicationException>
     */
    public static function resolveExceptionClass(ErrorType $type): string
    {
        if (!array_key_exists($type->name, self::TYPE_MAP)) {
            throw new RuntimeException("Unknown ErrorType: {$type->name}"); // @codeCoverageIgnore
        }

        return self::TYPE_MAP[$type->name];
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
