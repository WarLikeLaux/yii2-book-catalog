<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use app\infrastructure\services\observability\RequestIdProvider;
use yii\helpers\Json;
use yii\log\Logger;
use yii\log\Target;

/**
 * Отправляет логи Yii2 в Buggregator в формате Monolog (JSON).
 * @codeCoverageIgnore Дебаг-утилита, не требующая юнит-тестирования
 */
final class BuggregatorLogTarget extends Target
{
    public string $host = 'buggregator';

    public int $port = 9913;

    #[\Override]
    public function export(): void
    {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 2);
        if ($socket === false) {
            return;
        }

        foreach ($this->messages as $message) {
            [$text, $level, $category, $timestamp] = $message;

            $payload = [
                'message' => is_string($text) ? $text : Json::encode($text),
                'context' => [
                    'category' => $category,
                    'memory' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB',
                    'request_id' => RequestIdProvider::get(),
                ],
                'level' => $this->getMonologLevel($level),
                'level_name' => Logger::getLevelName($level),
                'channel' => 'yii2',
                'datetime' => date('Y-m-d H:i:s', (int)$timestamp),
                'extra' => [],
            ];

            fwrite($socket, Json::encode($payload) . "\n");
        }

        fclose($socket);
    }

    /**
     * Маппинг уровней Yii2 -> Monolog
     */
    private function getMonologLevel(int $yiiLevel): int
    {
        return match ($yiiLevel) {
            Logger::LEVEL_ERROR => 400,
            Logger::LEVEL_WARNING => 300,
            Logger::LEVEL_INFO => 200,
            Logger::LEVEL_TRACE => 100,
            Logger::LEVEL_PROFILE => 100,
            default => 200,
        };
    }
}
