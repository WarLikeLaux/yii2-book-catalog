<?php

declare(strict_types=1);

namespace app\commands\support;

use Symfony\Component\Yaml\Yaml;
use Yii;
use yii\console\Controller;

final readonly class ProjectMapPrinter
{
    public function print(Controller $output): void
    {
        $configPath = Yii::getAlias('@app/docs/structure.yaml');

        if (!is_string($configPath) || !file_exists($configPath)) {
            $output->stderr("Ошибка: Файл docs/structure.yaml не найден.\n");
            return;
        }

        $config = Yaml::parseFile($configPath);
        $modules = $config['modules'] ?? [];
        $layers = $config['layers'] ?? [];

        $output->stdout("КАРТА ПРОЕКТА (Clean Architecture + DDD)\n");
        $output->stdout("========================================\n\n");

        foreach ($layers as $dir => $data) {
            $this->renderLayer($output, $dir, $data, $modules);
        }

        $this->renderModulesSection($output, $modules);
    }

    private function renderLayer(Controller $output, string $dir, array $data, array $_modulesConfig): void
    {
        $output->stdout(sprintf("%-23s - %s\n", $dir . '/', $data['description'] ?? ''));

        if (isset($data['children'])) {
            foreach ($data['children'] as $child => $desc) {
                $output->stdout(sprintf("  ├── %-17s - %s\n", $child . '/', $desc));
            }
        }

        if (($data['type'] ?? '') === 'modules') {
            $output->stdout("  ├── {module}/         - См. раздел МОДУЛИ ниже\n");

            if (isset($data['module_structure'])) {
                foreach ($data['module_structure'] as $subdir => $desc) {
                    $output->stdout(sprintf("  │   ├── %-13s - %s\n", $subdir . '/', $desc));
                }
            }
        }

        $output->stdout("\n");
    }

    private function renderModulesSection(Controller $output, array $modules): void
    {
        $output->stdout("МОДУЛИ СИСТЕМЫ\n");
        $output->stdout("==============\n");

        foreach ($modules as $name => $desc) {
            $output->stdout(sprintf("  %-21s - %s\n", $name, $desc));
        }

        $output->stdout("\n");
    }
}
