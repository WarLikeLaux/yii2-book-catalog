<?php

declare(strict_types=1);

namespace app\domain\specifications;

final readonly class CompositeOrSpecification implements BookSpecificationInterface
{
    /**
     * @param BookSpecificationInterface[] $specifications
     */
    public function __construct(
        private array $specifications,
    ) {
    }

    public function accept(BookSpecificationVisitorInterface $visitor): void
    {
        $visitor->visitCompositeOr($this);
    }

    /**
     * @return BookSpecificationInterface[]
     */
    public function getSpecifications(): array
    {
        return $this->specifications;
    }
}
