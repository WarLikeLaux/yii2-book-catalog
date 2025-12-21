<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\domain\exceptions\DomainException;
use app\models\Author;

final class CreateAuthorUseCase
{
    public function execute(CreateAuthorCommand $command): Author
    {
        $author = new Author();
        $author->fio = $command->fio;

        if (!$author->save()) {
            throw new DomainException('Не удалось создать автора');
        }

        return $author;
    }
}
