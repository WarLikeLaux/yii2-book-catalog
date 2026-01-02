<?php

declare(strict_types=1);

namespace app\commands;

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use RuntimeException;
use Throwable;
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
            $this->stdout('Done! Data generated for current year (' . date('Y') . ") too.\n", Console::FG_GREEN);
            return ExitCode::OK;
        } catch (Throwable $e) {
            $transaction->rollBack();
            $this->stdout('Error: ' . $e->getMessage() . "\n", Console::FG_RED);
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
                throw new RuntimeException('Save author failed');
            }
            $ids[] = $author->id;
            if (!$author->isNewRecord) {
                continue;
            }

            $this->stdout("  Created author: {$name}\n");
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
            $isbn = $this->generateValidIsbn13();

            $year = rand(1, 100) <= 40 ? $currentYear : rand($currentYear - 5, $currentYear);
            $isPublished = rand(1, 100) <= 70;

            if (Book::find()->where(['isbn' => $isbn])->exists()) {
                continue;
            }

            $book = new Book([
                'title' => $title,
                'year' => $year,
                'isbn' => $isbn,
                'description' => "Автогенерированное описание для книги $title ($year).",
                'is_published' => $isPublished,
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

    private function generateValidIsbn13(): string
    {
        $prefix = '978';
        $group = (string)rand(0, 9);
        $publisher = str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
        $title = str_pad((string)rand(0, 99999), 5, '0', STR_PAD_LEFT);

        $isbn12 = $prefix . $group . $publisher . $title;

        $checksum = 0;
        for ($i = 0; $i < 12; $i++) {
            $weight = $i % 2 === 0 ? 1 : 3;
            $checksum += (int)$isbn12[$i] * $weight;
        }
        $checksumDigit = (10 - ($checksum % 10)) % 10;

        return "{$prefix}-{$group}-{$publisher}-{$title}-{$checksumDigit}";
    }
}
