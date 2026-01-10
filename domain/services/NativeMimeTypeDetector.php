<?php

declare(strict_types=1);

namespace app\domain\services;

use Closure;

final readonly class NativeMimeTypeDetector implements MimeTypeDetectorInterface
{
    private Closure $canUseMimeContentType;
    private Closure $detectWithMimeContentType;
    private Closure $canUseFinfo;
    private FinfoFunctions $finfoFunctions;

    public function __construct(
        ?Closure $canUseMimeContentType = null,
        ?Closure $detectWithMimeContentType = null,
        ?Closure $canUseFinfo = null,
        ?FinfoFunctions $finfoFunctions = null,
    ) {
        $this->canUseMimeContentType = $canUseMimeContentType
        ?? static fn(): bool => function_exists('mime_content_type');
        $this->detectWithMimeContentType = $detectWithMimeContentType
        ?? mime_content_type(...);
        $this->canUseFinfo = $canUseFinfo
        ?? static fn(): bool => function_exists('finfo_open');
        $this->finfoFunctions = $finfoFunctions ?? FinfoFunctions::fromNative();
    }

    public function detect(string $path): string
    {
        $mimeType = 'application/octet-stream';

        if (($this->canUseMimeContentType)()) {
            $mimeValue = ($this->detectWithMimeContentType)($path);
            return $mimeValue !== false ? $mimeValue : $mimeType;
        }

        if (($this->canUseFinfo)()) {
            $mimeValue = $this->detectWithFinfo($path);
            return $mimeValue !== false ? $mimeValue : $mimeType;
        }

        return $mimeType;
    }

    private function detectWithFinfo(string $path): string|false
    {
        $finfo = ($this->finfoFunctions->open)(FILEINFO_MIME_TYPE);
        $mimeValue = $finfo === false ? false : ($this->finfoFunctions->file)($finfo, $path);

        if ($finfo !== false) {
            ($this->finfoFunctions->close)($finfo);
        }

        return $mimeValue;
    }
}
