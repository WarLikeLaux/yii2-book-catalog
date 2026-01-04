<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yii;
use yii\log\Logger;

final readonly class YiiPsrLogger implements LoggerInterface
{
    private const array LEVEL_MAP = [
        LogLevel::EMERGENCY => Logger::LEVEL_ERROR,
        LogLevel::ALERT => Logger::LEVEL_ERROR,
        LogLevel::CRITICAL => Logger::LEVEL_ERROR,
        LogLevel::ERROR => Logger::LEVEL_ERROR,
        LogLevel::WARNING => Logger::LEVEL_WARNING,
        LogLevel::NOTICE => Logger::LEVEL_INFO,
        LogLevel::INFO => Logger::LEVEL_INFO,
        LogLevel::DEBUG => Logger::LEVEL_TRACE,
    ];

    public function __construct(
        private string $category = LogCategory::APPLICATION,
    ) {
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $levelKey = is_scalar($level) ? (string)$level : 'info';
        $yiiLevel = self::LEVEL_MAP[$levelKey] ?? Logger::LEVEL_INFO;
        $messageWithContext = $this->formatMessage((string)$message, $context);

        Yii::getLogger()->log($messageWithContext, $yiiLevel, $this->category);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function formatMessage(string $message, array $context): string
    {
        if ($context === []) {
            return $message;
        }

        $contextString = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return "{$message} | Context: {$contextString}";
    }
}
