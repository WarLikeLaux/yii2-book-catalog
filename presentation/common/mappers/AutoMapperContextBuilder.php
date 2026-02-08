<?php

declare(strict_types=1);

namespace app\presentation\common\mappers;

use AutoMapper\MapperContext;

final readonly class AutoMapperContextBuilder
{
    /**
     * @param array<class-string, array<string, mixed>> $arguments
     * @return array<string, mixed>
     * @phpstan-return array{
     *   groups?: array<string>|null,
     *   allowed_attributes?: array<array<string>|string>|null,
     *   ignored_attributes?: array<array<string>|string>|null,
     *   circular_reference_limit?: int|null,
     *   circular_reference_handler?: callable|null,
     *   circular_reference_registry?: array<string, mixed>,
     *   circular_count_reference_registry?: array<string, int>,
     *   depth?: int,
     *   target_to_populate?: mixed,
     *   deep_target_to_populate?: bool,
     *   constructor_arguments?: array<string, array<string, mixed>>,
     *   skip_null_values?: bool,
     *   skip_uninitialized_values?: bool,
     *   allow_readonly_target_to_populate?: bool,
     *   datetime_format?: string,
     *   datetime_force_timezone?: string,
     *   map_to_accessor_parameter?: array<string, string>,
     *   normalizer_format?: string
     * }
     */
    public function build(array $arguments): array
    {
        $context = new MapperContext();

        foreach ($arguments as $class => $values) {
            foreach ($values as $key => $value) {
                $context->setConstructorArgument($class, $key, $value);
            }
        }

        return $context->toArray();
    }
}
