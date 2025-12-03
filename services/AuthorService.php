<?php

declare(strict_types=1);

namespace app\services;

use app\models\Author;
use DomainException;

final class AuthorService
{
    public function create(string $fio): Author
    {
        $author = new Author();
        $author->fio = $fio;

        if (!$author->save()) {
            throw new DomainException('Не удалось создать автора');
        }

        return $author;
    }

    public function update(int $id, string $fio): Author
    {
        $author = Author::findOne($id);
        if (!$author) {
            throw new DomainException('Автор не найден');
        }

        $author->fio = $fio;

        if (!$author->save()) {
            throw new DomainException('Не удалось обновить автора');
        }

        return $author;
    }

    public function delete(int $id): void
    {
        $author = Author::findOne($id);
        if (!$author) {
            throw new DomainException('Автор не найден');
        }

        if (!$author->delete()) {
            throw new DomainException('Не удалось удалить автора');
        }
    }

    /**
     * @return array<int, string> Map of author IDs to names
     */
    public function getAuthorsMap(): array
    {
        return Author::find()
            ->select(['fio', 'id'])
            ->indexBy('id')
            ->orderBy(['fio' => SORT_ASC])
            ->column();
    }
}
