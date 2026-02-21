<?php

declare(strict_types=1);

namespace app\application\common;

enum IdempotencyKeyStatus: string
{
    case Started = 'started';
    case Finished = 'finished';
}
