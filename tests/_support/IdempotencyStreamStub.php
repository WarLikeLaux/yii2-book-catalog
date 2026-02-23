<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

final class StreamGetContentsStub
{
    public static bool $forceFalse = false;
}

function stream_get_contents($stream, int $length = -1, int $offset = -1): string|false
{
    if (StreamGetContentsStub::$forceFalse) {
        return false;
    }

    return \stream_get_contents($stream, $length, $offset);
}
