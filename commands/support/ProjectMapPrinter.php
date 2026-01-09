<?php

declare(strict_types=1);

namespace app\commands\support;

use Symfony\Component\Yaml\Yaml;
use Yii;
use yii\console\Controller;

final class ProjectMapPrinter
{
    private ?bool $isGitAvailable = null;
    private ?string $appPath = null;
    private ?Controller $output = null;

    public function print(Controller $output): void
    {
        $this->output = $output;
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
            if ($this->shouldSkipRoot($dir)) {
                continue;
            }

            $this->renderLayer($output, $dir, $data);
        }

        $this->renderModulesSection($output, $modules);
    }

    private function renderLayer(Controller $output, string $dir, array $data): void
    {
        $output->stdout(sprintf("%-23s - %s\n", $dir . '/', $data['description'] ?? ''));

        [
            $children,
            $configChildren,
            $actualChildren,
            $layerPath,
        ] = $this->collectLayerChildren($dir, $data);

        $isModules = ($data['type'] ?? '') === 'modules';
        $moduleStructure = $data['module_structure'] ?? [];
        $modulesExclude = $data['modules_exclude'] ?? [];
        $moduleDirs = $this->resolveModuleDirs($isModules, $actualChildren, $configChildren, $modulesExclude);

        $childrenToRender = $children;

        if ($isModules && $moduleDirs !== []) {
            $childrenToRender = array_values(array_diff($children, $moduleDirs));
        }

        foreach ($childrenToRender as $child) {
            $this->renderLayerChild($output, $child, $data, $isModules, $moduleDirs, $layerPath, $moduleStructure);
        }

        if (!$isModules || $moduleDirs === []) {
            return;
        }

        $this->renderModuleTemplate($output, $moduleStructure);
    }

    private function renderModulesSection(Controller $output, array $modules): void
    {
        $output->stdout("МОДУЛИ СИСТЕМЫ\n");
        $output->stdout("==============\n");

        foreach ($modules as $name => $desc) {
            $output->stdout(sprintf("  %-21s - %s\n", $name, $desc));
        }
    }

    /**
     * @return array{0: string[], 1: string[], 2: string[], 3: string}
     */
    private function collectLayerChildren(string $dir, array $data): array
    {
        $configChildren = array_keys($data['children'] ?? []);
        $excludedChildren = $data['children_exclude'] ?? [];
        $layerPath = Yii::getAlias('@app/' . $dir);

        if ($this->shouldSkipChildren($dir)) {
            return [$configChildren, $configChildren, [], $layerPath];
        }

        $actualChildren = $this->listDirectories($layerPath);
        $children = array_values(array_unique(array_merge($configChildren, $actualChildren)));

        if ($excludedChildren !== []) {
            $children = array_values(array_diff($children, $excludedChildren));
        }

        sort($children);

        return [$children, $configChildren, $actualChildren, $layerPath];
    }

    /**
     * @return string[]
     */
    private function resolveModuleDirs(
        bool $isModules,
        array $actualChildren,
        array $configChildren,
        array $modulesExclude,
    ): array {
        if (!$isModules) {
            return [];
        }

        $moduleDirs = array_values(array_diff($actualChildren, $configChildren, $modulesExclude));

        sort($moduleDirs);

        return $moduleDirs;
    }

    /**
     * @param string[] $moduleDirs
     * @param array<string, string> $moduleStructure
     */
    private function renderLayerChild(
        Controller $output,
        string $child,
        array $data,
        bool $isModules,
        array $moduleDirs,
        string $layerPath,
        array $moduleStructure,
    ): void {
        if ($isModules && in_array($child, $moduleDirs, true)) {
            $this->renderModuleChild($output, $child, $layerPath, $moduleStructure);
            return;
        }

        $desc = $data['children'][$child] ?? '';

        if ($desc !== '') {
            $output->stdout(sprintf("  ├── %-17s - %s\n", $child . '/', $desc));
            return;
        }

        $output->stdout(sprintf("  ├── %-17s\n", $child . '/'));
    }

    /**
     * @param array<string, string> $moduleStructure
     */
    private function renderModuleChild(
        Controller $output,
        string $child,
        string $layerPath,
        array $moduleStructure,
    ): void {
        $output->stdout(sprintf("  ├── %-17s\n", $child . '/'));
        $subdirs = $this->listDirectories($layerPath . '/' . $child);
        $subdirs = array_values(array_unique(array_merge(array_keys($moduleStructure), $subdirs)));
        sort($subdirs);

        foreach ($subdirs as $subdir) {
            $desc = $moduleStructure[$subdir] ?? '';

            if ($desc !== '') {
                $output->stdout(sprintf("  │   ├── %-13s - %s\n", $subdir . '/', $desc));
                continue;
            }

            $output->stdout(sprintf("  │   ├── %-13s\n", $subdir . '/'));
        }
    }

    /**
     * @param array<string, string> $moduleStructure
     */
    private function renderModuleTemplate(Controller $output, array $moduleStructure): void
    {
        $output->stdout(sprintf("  ├── %-17s\n", '{{module}}/'));

        if ($moduleStructure === []) {
            return;
        }

        $subdirs = array_keys($moduleStructure);
        sort($subdirs);

        foreach ($subdirs as $subdir) {
            $desc = $moduleStructure[$subdir] ?? '';

            if ($desc !== '') {
                $output->stdout(sprintf("  │   ├── %-13s - %s\n", $subdir . '/', $desc));
                continue;
            }

            $output->stdout(sprintf("  │   ├── %-13s\n", $subdir . '/'));
        }
    }

    /**
     * @return string[]
     */
    private function listDirectories(string $path): array
    {
        if (!is_dir($path) || !is_readable($path)) {
            return [];
        }

        $items = scandir($path);

        if (!is_array($items)) {
            return [];
        }

        $dirs = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;

            if (!is_dir($fullPath)) {
                continue;
            }

            $dirs[] = $item;
        }

        $dirs = $this->filterIgnoredDirectories($path, $dirs);
        $dirs = $this->filterUntrackedDirectories($path, $dirs);
        sort($dirs);

        return $dirs;
    }

    private function shouldSkipChildren(string $dir): bool
    {
        return in_array($dir, ['messages', 'runtime', 'vendor', 'web', 'tests'], true);
    }

    private function shouldSkipRoot(string $dir): bool
    {
        $path = Yii::getAlias('@app/' . $dir);

        return $this->isGitIgnored($path);
    }

    /**
     * @param string[] $dirs
     *
     * @return string[]
     */
    private function filterIgnoredDirectories(string $path, array $dirs): array
    {
        if ($dirs === [] || !$this->isGitAvailable()) {
            return $dirs;
        }

        $fullPaths = [];
        $dirMap = [];

        foreach ($dirs as $dir) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $dir;
            $fullPaths[] = $fullPath;
            $dirMap[$fullPath] = $dir;
        }

        $command = sprintf(
            'git -C %s check-ignore --stdin',
            escapeshellarg($this->getAppPath()),
        );

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            return $dirs;
        }

        fwrite($pipes[0], implode("\n", $fullPaths));
        fclose($pipes[0]);

        $ignoredPaths = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        $ignoredList = array_filter(explode("\n", (string)$ignoredPaths));
        $ignoredSet = array_flip($ignoredList);

        $filtered = [];

        foreach ($dirs as $dir) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $dir;

            if (isset($ignoredSet[$fullPath])) {
                continue;
            }

            $filtered[] = $dir;
        }

        return $filtered;
    }

    /**
     * @param string[] $dirs
     *
     * @return string[]
     */
    private function filterUntrackedDirectories(string $path, array $dirs): array
    {
        if ($dirs === [] || !$this->shouldFilterUntracked($path) || !$this->isGitAvailable()) {
            return $dirs;
        }

        $relativePaths = [];
        $dirMap = [];

        foreach ($dirs as $dir) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $dir;
            $relPath = $this->relativePath($fullPath);

            if ($relPath === '') {
                continue;
            }

            $relativePaths[] = $relPath;
            $dirMap[$relPath] = $dir;
        }

        if ($relativePaths === []) {
            return [];
        }

        $escapedPaths = array_map('escapeshellarg', $relativePaths);
        $command = sprintf(
            'git -C %s ls-files %s',
            escapeshellarg($this->getAppPath()),
            implode(' ', $escapedPaths),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        $trackedSet = array_flip($output);
        $filtered = [];

        foreach ($dirs as $dir) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $dir;
            $relPath = $this->relativePath($fullPath);

            if (!isset($trackedSet[$relPath])) {
                continue;
            }

            $filtered[] = $dir;
        }

        return $filtered;
    }

    private function isGitAvailable(): bool
    {
        if ($this->isGitAvailable !== null) {
            return $this->isGitAvailable;
        }

        if (!function_exists('exec')) {
            $this->output?->stderr("git недоступен — фильтры отключены\n");
            $this->isGitAvailable = false;
            return false;
        }

        $command = sprintf('git -C %s rev-parse --is-inside-work-tree', escapeshellarg($this->getAppPath()));
        $output = [];
        $exitCode = 0;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->output?->stderr("git недоступен — фильтры отключены\n");
            $this->isGitAvailable = false;
            return false;
        }

        $this->isGitAvailable = true;

        return true;
    }

    private function shouldFilterUntracked(string $path): bool
    {
        $relativePath = $this->relativePath($path);

        return str_starts_with($relativePath . '/', 'docs/');
    }

    private function isGitIgnored(string $path): bool
    {
        $relativePath = $this->relativePath($path);
        $command = sprintf('git -C %s check-ignore -q -- %s', escapeshellarg($this->getAppPath()), escapeshellarg($relativePath));
        $output = [];
        $exitCode = 0;
        @exec($command, $output, $exitCode);

        return $exitCode === 0;
    }

    private function getAppPath(): string
    {
        if ($this->appPath === null) {
            $this->appPath = Yii::getAlias('@app');
        }

        return $this->appPath;
    }

    private function relativePath(string $path): string
    {
        $root = $this->getAppPath();
        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (str_starts_with($path, $root)) {
            return ltrim(substr($path, strlen($root)), DIRECTORY_SEPARATOR);
        }

        return '';
    }
}
