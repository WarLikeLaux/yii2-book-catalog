<?php

declare(strict_types=1);

namespace app\infrastructure\repositories\decorators;

use app\application\ports\ReportRepositoryInterface;
use app\application\ports\TracerInterface;

final readonly class ReportRepositoryTracingDecorator implements ReportRepositoryInterface
{
    public function __construct(
        private ReportRepositoryInterface $repository,
        private TracerInterface $tracer,
    ) {
    }

    /**
     * @return array<array<string, mixed>>
     */
    #[\Override]
    public function getTopAuthorsByYear(int $year, int $limit): array
    {
        return $this->tracer->trace(
            'ReportRepo::' . __FUNCTION__,
            fn(): array => $this->repository->getTopAuthorsByYear($year, $limit),
        );
    }
}
