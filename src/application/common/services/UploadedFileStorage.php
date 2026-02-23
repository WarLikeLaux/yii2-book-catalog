<?php

declare(strict_types=1);

namespace app\application\common\services;

use app\application\common\dto\UploadedFilePayload;
use app\application\common\exceptions\ApplicationException;
use app\application\ports\ContentStorageInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\DomainException;
use app\domain\exceptions\ValidationException;
use app\domain\values\FileContent;

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
        } catch (DomainException $exception) {
            throw ApplicationException::fromDomainException($exception);
        }
    }

    private function createFileContent(UploadedFilePayload $payload): FileContent
    {
        if (!is_file($payload->path)) {
            throw new ValidationException(DomainErrorCode::FileNotFound);
        }

        if (!is_readable($payload->path)) {
            throw new ValidationException(DomainErrorCode::FileOpenFailed);
        }

        $stream = fopen($payload->path, 'rb');

        if ($stream === false) {
            throw new ValidationException(DomainErrorCode::FileOpenFailed); // @codeCoverageIgnore
        }

        $extension = $payload->extension !== ''
        ? $payload->extension
        : pathinfo($payload->path, PATHINFO_EXTENSION);

        return new FileContent($stream, $extension, $payload->mimeType);
    }
}
