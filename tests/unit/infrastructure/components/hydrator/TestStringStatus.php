<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\components\hydrator;

enum TestStringStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
