<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

use app\domain\exceptions\DomainErrorCode;

final class DomainErrorMappingRegistry
{
    /**
     * @var array<string, array{class-string<ApplicationException>, string|null}>
     */
    private array $mappings = [];

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
