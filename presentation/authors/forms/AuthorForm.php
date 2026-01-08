<?php

declare(strict_types=1);

namespace app\presentation\authors\forms;

use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\common\forms\RepositoryAwareForm;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Yii;

final class AuthorForm extends RepositoryAwareForm
{
    /** @var int|string|null */
    public $id;

    /** @var string|int|null */
    public $fio = '';

    #[\Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            [['fio'], 'required'],
            [['fio'], 'string', 'max' => 255],
            [['fio'], 'trim'],
            [['fio'], 'validateFioUnique'],
        ];
    }

    #[\Override]
    #[CodeCoverageIgnore]
    public function attributeLabels(): array
    {
        return [
            'fio' => Yii::t('app', 'ui.fio'),
        ];
    }

    /**
     * Validates that the given attribute's full name (FIO) is unique among authors.
     *
     * If another author exists with the same FIO (excluding the current model when `$this->id` is set),
     * a validation error is added to the attribute using the message key `author.error.fio_exists`.
     *
     * @param string $attribute The attribute name whose value contains the FIO to validate.
     */
    public function validateFioUnique(string $attribute): void
    {
        $value = $this->$attribute;

        if (!is_string($value)) {
            return; // @codeCoverageIgnore
        }

        $excludeId = $this->id !== null ? (int)$this->id : null;
        $queryService = $this->resolve(AuthorQueryServiceInterface::class);

        if (!$queryService->existsByFio($value, $excludeId)) {
            return;
        }

        $this->addError($attribute, Yii::t('app', 'author.error.fio_exists'));
    }
}