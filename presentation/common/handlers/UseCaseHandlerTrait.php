<?php

declare(strict_types=1);

namespace app\presentation\common\handlers;

use app\application\ports\CommandInterface;
use app\application\ports\UseCaseInterface;
use app\domain\exceptions\DomainException;
use app\presentation\common\services\WebUseCaseRunner;
use Yii;
use yii\base\Model;

trait UseCaseHandlerTrait
{
    /**
     * @return array<string, string>
     */
    protected function getErrorFieldMap(): array
    {
        return []; // @codeCoverageIgnore
    }

    /**
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     */
    protected function executeWithForm(
        WebUseCaseRunner $runner,
        Model $form,
        CommandInterface $command,
        UseCaseInterface $useCase,
        string $successMsg,
    ): mixed {
        return $runner->executeWithFormErrors(
            $command,
            $useCase,
            $successMsg,
            fn (DomainException $e) => $this->addFormError($form, $e),
        );
    }

    protected function addFormError(Model $form, DomainException $e): void
    {
        $errorCode = $e->errorCode->value;
        $errorToFieldMap = $this->getErrorFieldMap();
        $field = $errorToFieldMap[$errorCode] ?? null;
        $attributes = $form->attributes();

        if ($field === null || !in_array($field, $attributes, true)) {
            /** @var string|false $firstAttribute */
            $firstAttribute = reset($attributes);
            $field = $firstAttribute !== false ? $firstAttribute : null;
        }

        $form->addError($field ?? '', Yii::t('app', $errorCode));
    }
}
