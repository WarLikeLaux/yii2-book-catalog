<?php

declare(strict_types=1);

namespace app\presentation\common\handlers;

use app\application\common\exceptions\ApplicationException;
use Yii;
use yii\base\Model;

trait ErrorMappingTrait
{
    /**
     * @return array<string, string>
     */
    protected function getErrorFieldMap(): array
    {
        return [];
    }

    protected function addFormError(Model $form, ApplicationException $e): void
    {
        $errorCode = $e->errorCode;
        $errorToFieldMap = $this->getErrorFieldMap();
        $field = $errorToFieldMap[$errorCode] ?? null;
        $attributes = $form->attributes();

        if ($field === null || !in_array($field, $attributes, true)) {
            $preferred = ['title', 'name', 'fio', 'isbn', 'phone', 'authorIds', 'email', 'username'];

            foreach ($preferred as $candidate) {
                if (in_array($candidate, $attributes, true)) {
                    $field = $candidate;
                    break;
                }
            }
        }

        if ($field === null || !in_array($field, $attributes, true)) {
            $candidates = array_values(array_diff($attributes, ['id', 'version']));
            /** @var string|false $firstAttribute */
            $firstAttribute = reset($candidates);
            $field = $firstAttribute !== false ? $firstAttribute : null;
        }

        $form->addError($field ?? '', Yii::t('app', $errorCode));
    }
}
