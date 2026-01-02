<?php

declare(strict_types=1);

namespace app\domain\specifications;

final readonly class CompositeOrSpecification implements BookSpecificationInterface
{
    /**
     * @param BookSpecificationInterface[] $specifications
     */
    public function __construct(
        private array $specifications
    ) {
    }

    public function toSearchCriteria(): array
    {
        return [
            'type' => 'or',
            'value' => array_map(
                static fn(BookSpecificationInterface $spec): array => $spec->toSearchCriteria(),
                $this->specifications
            ),
        ];
    }
}
