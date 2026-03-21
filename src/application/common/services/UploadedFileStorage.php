<?php

declare(strict_types=1);

namespace app\application\common\services;

use app\application\common\dto\UploadedFilePayload;
use app\application\common\exceptions\ApplicationException;
use app\application\common\exceptions\StorageErrorCode;
use app\application\common\exceptions\StorageException;
use app\application\common\values\FileContent;
use app\application\ports\ContentStorageInterface;

final readonly class UploadedFileStorage
{
    public function __construct(
        private ContentStorageInterface $contentStorage,
    ) {
    }

    public function store(UploadedFilePayload $payload): string
    {
        try {
            $content = $this->createFileContent($payload);

            try {
                $fileKey = $this->contentStorage->save($content);
                return $fileKey->getExtendedPath($content->extension);
            } finally {
                $stream = $content->getStream();

                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        } catch (StorageException $exception) {
            throw new ApplicationException($exception->errorCode->value, $exception->getCode(), $exception);
        }
    }

    private function createFileContent(UploadedFilePayload $payload): FileContent
    {
        if (!is_file($payload->path)) {
            throw new StorageException(StorageErrorCode::NotFound);
        }

        if (!is_readable($payload->path)) {
            throw new StorageException(StorageErrorCode::OpenFailed);
        }

        $stream = fopen($payload->path, 'rb');

        if ($stream === false) {
            throw new StorageException(StorageErrorCode::OpenFailed); // @codeCoverageIgnore
        }

        $extension = $payload->extension !== ''
        ? $payload->extension
        : pathinfo($payload->path, PATHINFO_EXTENSION);

        return new FileContent($stream, $extension, $payload->mimeType);
    }
}
