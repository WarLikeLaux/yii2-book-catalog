<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\domain\exceptions\DomainException;
use app\models\Author;

final class DeleteAuthorUseCase
{
    public function execute(DeleteAuthorCommand $command): void
    {
        $author = Author::findOne($command->id);
        if (!$author) {
            throw new DomainException('Автор не найден');
        }

        if (!$author->delete()) {
            throw new DomainException('Не удалось удалить автора');
        }
    }
}
