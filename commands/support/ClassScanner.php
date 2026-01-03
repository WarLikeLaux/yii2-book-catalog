<?php

declare(strict_types=1);

namespace app\commands\support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;

final class ClassScanner
{
    /**
     * @param array<string, int> $targets Relative dirs with priorities
     */
    public function __construct(
        private readonly string $basePath,
        private readonly array $targets,
    ) {
    }

    /**
     * @return array{0: string[], 1: array<string, string[]>}
     */
    public function scanAndAlias(): array
    {
        $shortNameMap = [];
        $conflicts = [];

        foreach ($this->targets as $relativeDir => $priority) {
            foreach ($this->scanClasses($relativeDir) as $fqcn) {
                $shortName = substr($fqcn, (int) strrpos($fqcn, '\\') + 1);
                if ($shortName === '') {
                    continue;
                }

                if (isset($shortNameMap[$shortName])) {
                    if ($shortNameMap[$shortName]['priority'] <= $priority) {
                        $conflicts[$shortName][] = $fqcn;
                        continue;
                    }
                }

                $shortNameMap[$shortName] = ['fqcn' => $fqcn, 'priority' => $priority];
            }
        }

        return $this->applyAliases($shortNameMap, $conflicts);
    }

    /**
     * @return array<class-string>
     */
    private function scanClasses(string $relativeDir): array
    {
        $directory = $this->basePath . DIRECTORY_SEPARATOR . $relativeDir;
        if (!is_dir($directory)) {
            return [];
        }

        $classes = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );
        } catch (UnexpectedValueException) {
            return [];
        }

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue;
            }
            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            foreach ($this->extractClassesFromFile($fileInfo->getPathname()) as $fqcn) {
                $classes[] = $fqcn;
            }
        }

        return $classes;
    }

    /**
     * @return array<class-string>
     */
    private function extractClassesFromFile(string $filePath): array
    {
        $source = file_get_contents($filePath);
        if (!is_string($source) || $source === '') {
            return [];
        }

        $tokens = token_get_all($source);
        $namespace = '';
        $classes = [];

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->collectNamespace($tokens, $i + 1);
                continue;
            }

            if ($token[0] !== T_CLASS && $token[0] !== T_INTERFACE && $token[0] !== T_TRAIT) {
                continue;
            }

            if ($this->isAnonymousClass($tokens, $i)) {
                continue;
            }

            $name = $this->collectClassName($tokens, $i + 1);
            if ($name === '') {
                continue;
            }

            $classes[] = $namespace !== '' ? $namespace . '\\' . $name : $name;
        }

        return $classes;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function collectNamespace(array $tokens, int $start): string
    {
        $parts = [];

        for ($i = $start, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_string($token)) {
                if ($token === ';' || $token === '{') {
                    break;
                }
                continue;
            }

            if ($token[0] === T_STRING || $token[0] === T_NAME_QUALIFIED) {
                $parts[] = $token[1];
            } elseif ($token[0] === T_NS_SEPARATOR) {
                $parts[] = '\\';
            }
        }

        return str_replace('\\\\', '\\', implode('', $parts));
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function collectClassName(array $tokens, int $start): string
    {
        for ($i = $start, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token)) {
                continue;
            }
            if ($token[0] === T_STRING) {
                return $token[1];
            }
        }

        return '';
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function isAnonymousClass(array $tokens, int $index): bool
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if (is_array($token) && ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT)) {
                continue;
            }

            return is_array($token) && $token[0] === T_NEW;
        }

        return false;
    }

    /**
     * @param array<string, array{fqcn: class-string, priority: int}> $shortNameMap
     * @param array<string, string[]> $conflicts
     * @return array{0: string[], 1: array<string, string[]>}
     */
    private function applyAliases(array $shortNameMap, array $conflicts): array
    {
        $aliased = [];

        foreach ($shortNameMap as $shortName => $entry) {
            $fqcn = $entry['fqcn'];
            if (class_exists($shortName, false) || interface_exists($shortName, false)) {
                continue;
            }

            if (!class_exists($fqcn) && !interface_exists($fqcn) && !trait_exists($fqcn)) {
                continue;
            }

            $reflection = new \ReflectionClass($fqcn);
            if ($reflection->isTrait()) {
                continue;
            }

            if (!class_alias($fqcn, $shortName, false)) {
                $conflicts[$shortName][] = $fqcn;
                continue;
            }

            $aliased[] = $shortName;
        }

        return [$aliased, $conflicts];
    }
}
