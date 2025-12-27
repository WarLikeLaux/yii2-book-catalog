<?php

declare(strict_types=1);

namespace app\tests\unit;

use Codeception\Test\Unit;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

final class ArchitectureTest extends Unit
{
    /**
     * Проверяем, что все UseCase классы объявлены как final.
     * Это гарантирует, что бизнес-логика не будет неявно расширена.
     */
    public function testUseCasesAreFinal(): void
    {
        $useCasePath = __DIR__ . '/../../application';
        $files = $this->getPhpFiles($useCasePath);

        foreach ($files as $file) {
            if (!str_ends_with($file, 'UseCase.php')) {
                continue;
            }

            $className = $this->getClassNameFromFile($file);
            if (!$className || !class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isInterface() || $reflection->isAbstract()) {
                continue;
            }

            $this->assertTrue(
                $reflection->isFinal(),
                "UseCase class {$className} must be final."
            );
        }
    }

    /**
     * Проверяем, что доменный слой не зависит от глобального Yii::$app
     * и других инфраструктурных классов.
     */
    public function testDomainIsClean(): void
    {
        $domainPath = __DIR__ . '/../../domain';
        $files = $this->getPhpFiles($domainPath);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            $this->assertStringNotContainsString(
                'Yii::$app',
                $content,
                "Domain file {$file} must not depend on Yii::\$app."
            );

            $this->assertStringNotContainsString(
                'app\infrastructure',
                $content,
                "Domain file {$file} must not depend on infrastructure layer."
            );
        }
    }

    /**
     * Проверяем, что все Value Objects в домене являются final.
     */
    public function testValueObjectsAreFinal(): void
    {
        $voPath = __DIR__ . '/../../domain/values';
        if (!is_dir($voPath)) return;

        $files = $this->getPhpFiles($voPath);

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);
            if (!$className || !class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            $this->assertTrue(
                $reflection->isFinal(),
                "Value Object {$className} must be final."
            );
        }
    }

    private function getPhpFiles(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);
        $files = [];

        foreach ($iterator as $info) {
            if ($info->isFile() && $info->getExtension() === 'php') {
                $files[] = $info->getPathname();
            }
        }

        return $files;
    }

    private function getClassNameFromFile(string $file): string|null
    {
        $content = file_get_contents($file);
        if (!preg_match('/namespace\s+(.+);/', $content, $nsMatches)) {
            return null;
        }

        if (!preg_match('/(?:class|interface|enum)\s+(\w+)/', $content, $classMatches)) {
            return null;
        }

        return $nsMatches[1] . '\\' . $classMatches[1];
    }
}
