<?php

declare(strict_types=1);

namespace app\presentation\authors\forms;

use app\application\ports\AuthorRepositoryInterface;
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
            'fio' => Yii::t('app', 'FIO'),
        ];
    }

    public function validateFioUnique(string $attribute): void
    {
        $value = $this->$attribute;

        if (!is_string($value)) {
            return; // @codeCoverageIgnore
        }

        $excludeId = $this->id !== null ? (int)$this->id : null;
        $repository = $this->resolve(AuthorRepositoryInterface::class);

        if (!$repository->existsByFio($value, $excludeId)) {
            return;
        }

        $this->addError($attribute, Yii::t('app', 'Author with this FIO already exists'));
    }
}
