<?php

declare(strict_types=1);

namespace app\application\system\usecases;

use app\application\common\dto\HealthReport;
use app\application\ports\HealthCheckRunnerInterface;
use app\application\ports\UseCaseInterface;
use app\application\system\commands\CheckHealthCommand;

/**
 * @implements UseCaseInterface<CheckHealthCommand, HealthReport>
 */
final readonly class CheckHealthUseCase implements UseCaseInterface
{
    public function __construct(
        private HealthCheckRunnerInterface $runner,
    ) {
    }

    /**
     * @param CheckHealthCommand $_command
     */
    public function execute(object $_command): HealthReport
    {
        return $this->runner->run();
    }
}
