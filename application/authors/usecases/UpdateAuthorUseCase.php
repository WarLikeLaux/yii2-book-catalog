<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\domain\exceptions\DomainException;
use app\models\Author;

final class UpdateAuthorUseCase
{
    public function execute(UpdateAuthorCommand $command): Author
    {
        $author = Author::findOne($command->id);
        if (!$author) {
            throw new DomainException('Автор не найден');
        }

        $author->fio = $command->fio;

        if (!$author->save()) {
            throw new DomainException('Не удалось обновить автора');
        }

        return $author;
    }
}
