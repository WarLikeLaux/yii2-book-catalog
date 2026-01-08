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
     * Execute a use case using a form and map domain validation errors onto the form.
     *
     * Executes the provided use case via the given WebUseCaseRunner and, if a DomainException
     * occurs, maps the exception message to a form field error using addFormError.
     *
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param WebUseCaseRunner $runner Runner that executes the use case and handles form errors.
     * @param Model $form The form model to which validation errors will be added.
     * @param TCommand $command The command object to pass to the use case.
     * @param UseCaseInterface<TCommand, TResponse> $useCase The use case to execute.
     * @param string $successMsg Message passed to the runner to use on successful execution.
     * @return mixed The value returned by the WebUseCaseRunner after execution (typically the use-case response or a runner-specific result).
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

    /**
     * Maps a DomainException to a form field and adds a translated validation error to the form.
     *
     * Looks up the exception message in static::ERROR_TO_FIELD_MAP (if defined) to select the target field.
     * If no mapping exists, uses the form's first attribute or `'id'` as a fallback. The exception message
     * is translated with Yii::t('app', ...) before being added to the form.
     *
     * @param Model $form The form model that will receive the validation error.
     * @param DomainException $e The domain exception whose message determines the error content and (optionally) the target field.
     * @return void
     */
    protected function addFormError(Model $form, DomainException $e): void
    {
        $errorToFieldMap = defined('static::ERROR_TO_FIELD_MAP') ? static::ERROR_TO_FIELD_MAP : [];
        $field = $errorToFieldMap[$e->getMessage()] ?? null;
        $message = Yii::t('app', $e->getMessage());

        if ($field === null) {
            $attributes = $form->attributes();
            $firstAttribute = reset($attributes);
            $field = $firstAttribute !== false ? $firstAttribute : 'id';
        }

        $form->addError($field, $message);
    }
}