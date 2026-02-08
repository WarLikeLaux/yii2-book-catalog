<?php

declare(strict_types=1);

namespace app\application\common\services;

use app\application\common\dto\UploadedFilePayload;
use app\application\common\exceptions\ApplicationException;
use app\application\ports\ContentStorageInterface;
use app\domain\exceptions\DomainException;
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
            $content = FileContent::fromPath($payload->path, $payload->extension, $payload->mimeType);
            $fileKey = $this->contentStorage->save($content);
            return $fileKey->getExtendedPath($content->extension);
        } catch (DomainException $exception) {
            throw ApplicationException::fromDomainException($exception);
        }
    }
}
