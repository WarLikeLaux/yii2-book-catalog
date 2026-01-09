<?php

declare(strict_types=1);

namespace app\presentation\common\widgets;

use app\application\ports\SystemInfoProviderInterface;
use Yii;
use yii\base\Widget;

final class SystemInfoWidget extends Widget
{
    private readonly SystemInfoProviderInterface $provider;

    public function __construct(
        $config = [],
    ) {
        /** @var SystemInfoProviderInterface $provider */
        $provider = Yii::$container->get(SystemInfoProviderInterface::class);
        $this->provider = $provider;

        parent::__construct($config);
    }

    #[\Override]
    public function run(): string
    {
        $info = $this->provider->getInfo();

        $dbLink = str_contains(strtolower($info->dbDriver), 'mysql') ? 'https://www.mysql.com/' : 'https://www.postgresql.org/';

        $dbKey = str_contains(strtolower($info->dbDriver), 'mysql') ? 'mysql' : 'postgresql';

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
