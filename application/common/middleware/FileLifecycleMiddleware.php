<?php

declare(strict_types=1);

namespace app\application\common\middleware;

use app\application\ports\CommandInterface;
use app\application\ports\FileStorageInterface;
use app\application\ports\MiddlewareInterface;
use app\domain\values\StoredFileReference;
use Throwable;

final readonly class FileLifecycleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private FileStorageInterface $fileStorage,
    ) {
    }

    public function process(CommandInterface $command, callable $next): mixed
    {
        $fileRef = $this->extractFileReference($command);

        try {
            return $next($command);
        } catch (Throwable $e) {
            if ($fileRef instanceof StoredFileReference) {
                $this->fileStorage->delete((string) $fileRef);
            }

            throw $e;
        }
    }

    private function extractFileReference(CommandInterface $command): StoredFileReference|null
    {
        if (!property_exists($command, 'cover')) {
            return null;
        }

        /** @var mixed $cover */
        $cover = $command->cover;

        return $cover instanceof StoredFileReference ? $cover : null;
    }
}
