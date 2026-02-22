<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\ports\CoverKeysScannerInterface;
use app\infrastructure\persistence\Book;
use yii\db\Connection;

final readonly class CoverKeysScanner implements CoverKeysScannerInterface
{
    public function __construct(
        private Connection $db,
    ) {
    }

    /**
     * @return string[]
     */
    public function getReferencedCoverKeys(): array
    {
        $urls = Book::find()
            ->select('cover_url')
            ->where(['IS NOT', 'cover_url', null])
            ->column($this->db);

        $urls = array_values(array_filter(
            $urls,
            static fn(mixed $value): bool => is_string($value) && $value !== '',
        ));

        $keys = array_map(
            $this->extractCoverKeyFromUrl(...),
            $urls,
        );

        $keys = array_filter($keys, static fn(string $key): bool => $key !== '');

        return array_values(array_unique($keys));
    }

    private function extractCoverKeyFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        $target = is_string($path) && $path !== '' ? $path : preg_split('/[?#]/', $url, 2)[0] ?? '';

        return pathinfo($target, PATHINFO_FILENAME);
    }
}
