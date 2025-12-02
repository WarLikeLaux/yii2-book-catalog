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
            throw new DomainException('Failed to create author');
        }

        return $author;
    }

    public function update(int $id, string $fio): Author
    {
        $author = Author::findOne($id);
        if (!$author) {
            throw new DomainException('Author not found');
        }

        $author->fio = $fio;

        if (!$author->save()) {
            throw new DomainException('Failed to update author');
        }

        return $author;
    }

    public function delete(int $id): void
    {
        $author = Author::findOne($id);
        if (!$author) {
            throw new DomainException('Author not found');
        }

        if (!$author->delete()) {
            throw new DomainException('Failed to delete author');
        }
    }
}

