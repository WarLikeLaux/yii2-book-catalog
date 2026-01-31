<?php

declare(strict_types=1);

namespace app\infrastructure\adapters;

use app\application\common\dto\SystemInfoDto;
use app\application\ports\SystemInfoProviderInterface;
use Override;
use PDO;
use Throwable;
use yii\BaseYii;
use yii\db\Connection;

final readonly class SystemInfoAdapter implements SystemInfoProviderInterface
{
    public function __construct(
        private Connection $db,
    ) {
    }

    #[Override]
    public function getInfo(): SystemInfoDto
    {
        return new SystemInfoDto(
            phpVersion: PHP_VERSION,
            yiiVersion: BaseYii::getVersion(),
            dbDriver: strtoupper((string)$this->db->driverName),
            dbVersion: $this->getDbVersion(),
        );
    }

    private function getDbVersion(): string
    {
        try {
            /** @var string|null $version */
            $version = $this->db->getSlavePdo()?->getAttribute(PDO::ATTR_SERVER_VERSION);
            return $version ?? 'unknown';
        } catch (Throwable) {
            return 'unknown';
        }
    }
}
