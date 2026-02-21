<?php

declare(strict_types=1);

namespace app\application\common\config;

use app\application\common\exceptions\ConfigurationException;

final readonly class ConfigReader
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        private array $params,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function requireSection(string $key): array
    {
        $section = $this->params[$key] ?? null;

        return $this->requireArraySection($section, $key);
    }

    /**
     * @param array<string, mixed> $section
     *
     * @return array<string, mixed>
     */
    public function requireSubsection(array $section, string $sectionName, string $key): array
    {
        $value = $section[$key] ?? null;

        return $this->requireArraySection($value, $sectionName . '.' . $key);
    }

    /**
     * @param array<string, mixed> $section
     */
    public function requireInt(array $section, string $sectionName, string $key): int
    {
        $value = $section[$key] ?? null;

        if (!is_int($value)) {
            throw new ConfigurationException('Invalid config: ' . $sectionName . '.' . $key);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $section
     */
    public function requireString(array $section, string $sectionName, string $key): string
    {
        $value = $section[$key] ?? null;

        if (!is_string($value)) {
            throw new ConfigurationException('Invalid config: ' . $sectionName . '.' . $key);
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function requireArraySection(mixed $value, string $path): array
    {
        if (!is_array($value)) {
            throw new ConfigurationException('Missing required config: ' . $path);
        }

        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                throw new ConfigurationException('Invalid config: ' . $path);
            }
        }

        /** @var array<string, mixed> $value */
        return $value;
    }
}
