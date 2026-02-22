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
        $configPath = $root . '/phpstan-rule-test-violation.neon';

        $output = [];
        $exitCode = 0;
        exec(
            sprintf(
                'cd %s && ./vendor/bin/phpstan analyse --no-progress --memory-limit=512M --error-format=json -c %s 2>&1',
                escapeshellarg($root),
                escapeshellarg($configPath),
            ),
            $output,
            $exitCode,
        );

        $outputStr = implode("\n", $output);
        $this->assertSame(1, $exitCode, 'PHPStan must report errors for ghost QueryService. Output: ' . $outputStr);

        $data = json_decode($outputStr, true);
        $this->assertIsArray($data, 'PHPStan output must be valid JSON. Output: ' . $outputStr);

        $messages = [];

        foreach ($data['files'] ?? [] as $fileErrors) {
            foreach ($fileErrors['messages'] as $message) {
                $messages[] = $message;
            }
        }

        $identifiers = array_column($messages, 'identifier');
        $this->assertContains(
            'architecture.noGhostQueryService',
            $identifiers,
            'Error must contain rule identifier. Output: ' . $outputStr,
        );

        $found = false;

        foreach ($messages as $message) {
            if ($message['identifier'] === 'architecture.noGhostQueryService') {
                $this->assertStringContainsString(
                    'Ghost QueryService in Application layer is forbidden',
                    $message['message'],
                    'Error must contain diagnostic message. Output: ' . $outputStr,
                );
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Target error was not matched in PHPStan output.');
    }

    public function testValidDtoDoesNotTriggerError(): void
    {
        $root = dirname(__DIR__, 4);
        $configPath = $root . '/phpstan-rule-test-valid.neon';

        $output = [];
        $exitCode = 0;
        exec(
            sprintf(
                'cd %s && ./vendor/bin/phpstan analyse --no-progress --memory-limit=512M --error-format=json -c %s 2>&1',
                escapeshellarg($root),
                escapeshellarg($configPath),
            ),
            $output,
            $exitCode,
        );

        $outputStr = implode("\n", $output);
        $this->assertSame(0, $exitCode, 'Valid DTO must not trigger rule. Output: ' . $outputStr);
    }
}
