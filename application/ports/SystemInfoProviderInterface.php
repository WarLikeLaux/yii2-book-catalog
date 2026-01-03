<?php

declare(strict_types=1);

namespace app\application\ports;

use app\application\common\dto\SystemInfoDto;

interface SystemInfoProviderInterface
{
    public function getInfo(): SystemInfoDto;
}
