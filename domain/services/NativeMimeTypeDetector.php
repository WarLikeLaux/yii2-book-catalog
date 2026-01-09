<?php

declare(strict_types=1);

namespace app\domain\services;

use Closure;

final readonly class NativeMimeTypeDetector implements MimeTypeDetectorInterface
{
    private Closure $canUseMimeContentType;
    private Closure $detectWithMimeContentType;
    private Closure $canUseFinfo;
    private Closure $detectWithFinfo;

    public function __construct(
        ?Closure $canUseMimeContentType = null,
        ?Closure $detectWithMimeContentType = null,
        ?Closure $canUseFinfo = null,
        ?Closure $detectWithFinfo = null,
    ) {
        $this->canUseMimeContentType = $canUseMimeContentType
        ?? static fn(): bool => function_exists('mime_content_type');
        $this->detectWithMimeContentType = $detectWithMimeContentType
        ?? mime_content_type(...);
        $this->canUseFinfo = $canUseFinfo
        ?? static fn(): bool => function_exists('finfo_open');
        $this->detectWithFinfo = $detectWithFinfo ?? static function (string $path): string|false {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeValue = $finfo === false ? false : finfo_file($finfo, $path);

            if ($finfo !== false) {
                finfo_close($finfo);
            }

            return $mimeValue;
        };
    }

    public function detect(string $path): string
    {
        $mimeType = 'application/octet-stream';

        if (($this->canUseMimeContentType)()) {
            $mimeValue = ($this->detectWithMimeContentType)($path);
            return $mimeValue !== false ? $mimeValue : $mimeType;
        }

        if (($this->canUseFinfo)()) {
            $mimeValue = ($this->detectWithFinfo)($path);
            return $mimeValue !== false ? $mimeValue : $mimeType;
        }

        return $mimeType;
    }
}
