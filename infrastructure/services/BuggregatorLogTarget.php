<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use app\infrastructure\services\observability\RequestIdProvider;
use Throwable;
use yii\helpers\Json;
use yii\log\Logger;
use yii\log\Target;

/**
 * @codeCoverageIgnore
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

            $context = [
                'category' => $category,
                'memory' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB',
                'request_id' => RequestIdProvider::get(),
            ];

            $messageText = $this->extractMessage($text);

            if ($text instanceof Throwable) {
                $context['exception'] = $this->extractExceptionContext($text);
            }

            $payload = [
                'message' => $messageText,
                'context' => $context,
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

    private function extractMessage(mixed $text): string
    {
        if (is_string($text)) {
            return $text;
        }

        if ($text instanceof Throwable) {
            return sprintf(
                '[%s] %s',
                $text::class,
                $text->getMessage(),
            );
        }

        return Json::encode($text);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractExceptionContext(Throwable $exception): array
    {
        $context = [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatTrace($exception),
        ];

        $previous = $exception->getPrevious();

        if ($previous instanceof Throwable) {
            $context['previous'] = $this->extractExceptionContext($previous);
        }

        return $context;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatTrace(Throwable $exception): array
    {
        $trace = [];

        foreach ($exception->getTrace() as $frame) {
            $trace[] = [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'],
                'class' => $frame['class'] ?? null,
            ];
        }

        return $trace;
    }

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
