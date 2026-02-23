<?php

declare(strict_types=1);

namespace app\presentation\common\widgets;

use app\application\ports\SystemInfoProviderInterface;
use Override;
use yii\base\Widget;

final class SystemInfoWidget extends Widget
{
    public function __construct(
        private readonly SystemInfoProviderInterface $provider,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    #[Override]
    public function run(): string
    {
        $info = $this->provider->getInfo();

        $isMySql = str_contains(strtolower($info->dbDriver), 'mysql');
        $dbLink = $isMySql ? 'https://www.mysql.com/' : 'https://www.postgresql.org/';
        $dbKey = $isMySql ? 'mysql' : 'postgresql';

        $items = [
            [
                'label' => 'PHP',
                'value' => $info->phpVersion,
                'url' => 'https://www.php.net/',
                'logoUrl' => 'https://cdn.simpleicons.org/php/777BB4',
            ],
            [
                'label' => 'Yii2',
                'value' => $info->yiiVersion,
                'url' => 'https://www.yiiframework.com/',
                'logoUrl' => 'https://cdn.simpleicons.org/yii/008CC1',
            ],
            [
                'label' => $info->dbDriver,
                'value' => $info->dbVersion,
                'url' => $dbLink,
                'logoUrl' => "https://cdn.simpleicons.org/{$dbKey}/4479A1",
            ],
        ];

        return $this->render('system-info', [
            'items' => $items,
        ]);
    }
}
