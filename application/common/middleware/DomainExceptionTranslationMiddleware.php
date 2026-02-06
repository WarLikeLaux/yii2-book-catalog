<?php

declare(strict_types=1);

namespace app\application\common\middleware;

use app\application\common\exceptions\DomainErrorMappingRegistry;
use app\application\ports\CommandInterface;
use app\application\ports\MiddlewareInterface;
use app\domain\exceptions\DomainException;

final readonly class DomainExceptionTranslationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private DomainErrorMappingRegistry $registry,
    ) {
    }

    public function process(CommandInterface $command, callable $next): mixed
    {
        try {
            return $next($command);
        } catch (DomainException $e) {
            $mapping = $this->registry->getMapping($e->errorCode);

            if ($mapping === null) {
                throw $e;
            }

            [$exceptionClass, $field] = $mapping;

            throw new $exceptionClass(
                errorCode: $e->errorCode->value,
                field: $field,
                code: $e->getCode(),
                previous: $e,
            );
        }
    }
}
