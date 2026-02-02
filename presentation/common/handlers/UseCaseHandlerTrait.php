<?php

declare(strict_types=1);

namespace app\presentation\common\handlers;

use app\application\common\exceptions\ApplicationException;
use app\application\ports\CommandInterface;
use app\application\ports\UseCaseInterface;
use app\presentation\common\services\WebOperationRunner;
use yii\base\Model;

trait UseCaseHandlerTrait
{
    use ErrorMappingTrait;

    /**
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     */
    protected function executeWithForm(
        WebOperationRunner $runner,
        Model $form,
        CommandInterface $command,
        UseCaseInterface $useCase,
        string $successMsg,
    ): mixed {
        return $runner->executeWithFormErrors(
            $command,
            $useCase,
            $successMsg,
            fn (ApplicationException $e) => $this->addFormError($form, $e),
        );
    }
}
