<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Author;
use app\models\Book;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

final class SeedController extends Controller
{
    public function actionIndex(): int
    {
        $this->stdout("Seeding database...\n", Console::FG_GREEN);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $authors = $this->seedAuthors();
            $this->seedBooks($authors);

            $transaction->commit();
            $this->stdout("Done! Data generated for current year (" . date('Y') . ") too.\n", Console::FG_GREEN);
            return ExitCode::OK;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stdout("Error: " . $e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    private function seedAuthors(): array
    {
        $this->stdout("Seeding authors...\n");

        $names = [
            'Лев Толстой',
            'Фёдор Достоевский',
            'Стивен Кинг',
            'Джоан Роулинг',
            'Джордж Мартин',
            'Агата Кристи',
            'Эрих Мария Ремарк',
            'Харуки Мураками',
            'Нил Гейман',
            'Айзек Азимов',
            'Рэй Брэдбери',
            'Говард Лавкрафт',
        ];

        $ids = [];
        foreach ($names as $name) {
            $author = Author::findOne(['fio' => $name]) ?? new Author(['fio' => $name]);
            if ($author->isNewRecord && !$author->save()) {
                throw new \RuntimeException('Save author failed');
            }
            $ids[] = $author->id;
            if ($author->isNewRecord) {
                $this->stdout("  Created author: {$name}\n");
            }
        }

        return $ids;
    }

    private function seedBooks(array $authorIds): void
    {
        $this->stdout("Seeding books...\n");

        $adjectives = ['Тёмный', 'Светлый', 'Вечный', 'Последний', 'Забытый', 'Великий'];
        $nouns = ['Рыцарь', 'Город', 'Лес', 'Океан', 'Странник', 'Замок', 'Космос'];

        $currentYear = (int)date('Y');

        for ($i = 0; $i < 50; $i++) {
            $title = $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)] . ' #' . ($i + 1);
            $isbn = '978-' . rand(1, 9) . '-' . rand(100, 999) . '-' . rand(10000, 99999) . '-' . rand(0, 9);

            $year = (rand(1, 100) <= 40) ? $currentYear : rand($currentYear - 5, $currentYear);

            if (Book::find()->where(['isbn' => $isbn])->exists()) {
                continue;
            }

            $book = new Book([
                'title' => $title,
                'year' => $year,
                'isbn' => $isbn,
                'description' => "Автогенерированное описание для книги $title ($year).",
            ]);

            if (!$book->save()) {
                continue;
            }

            $randomAuthorIds = array_rand(array_flip($authorIds), min(rand(1, 3), count($authorIds)));
            $randomAuthorIds = is_array($randomAuthorIds) ? $randomAuthorIds : [$randomAuthorIds];

            foreach ($randomAuthorIds as $authorId) {
                Yii::$app->db->createCommand()->insert('book_authors', [
                    'book_id' => $book->id,
                    'author_id' => $authorId,
                ])->execute();
            }

            $this->stdout("  Created book: {$title} ({$year})\n");
        }
    }
}
