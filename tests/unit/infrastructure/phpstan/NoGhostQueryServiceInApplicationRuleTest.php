<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\phpstan;

use Codeception\Test\Unit;

/**
 * @codeCoverageIgnore Логика правила проверяется через PHPStan
 */
final class NoGhostQueryServiceInApplicationRuleTest extends Unit
{
    public function testGhostQueryServiceInApplicationTriggersError(): void
    {
        $root = dirname(__DIR__, 4);
        $configPath = $root . '/phpstan-rule-test.neon';
        $fixtureDir = $root . '/phpstan-fixtures/NoGhostQueryServiceInApplicationRule';

        $output = [];
        $exitCode = 0;
        exec(
            sprintf(
                'cd %s && ./vendor/bin/phpstan analyse --no-progress --memory-limit=512M -c %s %s 2>&1',
                escapeshellarg($root),
                escapeshellarg($configPath),
                escapeshellarg($fixtureDir),
            ),
            $output,
            $exitCode,
        );

        $outputStr = implode("\n", $output);

        $this->assertSame(1, $exitCode, 'PHPStan must report errors for ghost QueryService. Output: ' . $outputStr);
        $this->assertStringContainsString(
            'architecture.noGhostQueryService',
            $outputStr,
            'Error must contain rule identifier. Output: ' . $outputStr,
        );
        $this->assertStringContainsString(
            'Ghost QueryService in Application layer is forbidden',
            $outputStr,
            'Error must contain diagnostic message. Output: ' . $outputStr,
        );
    }

    public function testValidDtoDoesNotTriggerError(): void
    {
        $root = dirname(__DIR__, 4);
        $configPath = $root . '/phpstan-rule-test.neon';
        $validFile = $root . '/phpstan-fixtures/NoGhostQueryServiceInApplicationRule/valid-dto-no-violation.php';

        $output = [];
        $exitCode = 0;
        exec(
            sprintf(
                'cd %s && ./vendor/bin/phpstan analyse --no-progress --memory-limit=512M -c %s %s 2>&1',
                escapeshellarg($root),
                escapeshellarg($configPath),
                escapeshellarg($validFile),
            ),
            $output,
            $exitCode,
        );

        $outputStr = implode("\n", $output);

        $this->assertSame(0, $exitCode, 'Valid DTO must not trigger rule. Output: ' . $outputStr);
    }
}
