<?php

declare(strict_types=1);

namespace app\infrastructure\services\sms;

use app\application\ports\SmsSenderInterface;
use Psr\Log\LoggerInterface;

/** @codeCoverageIgnore Интеграция с внешним API (SmsPilot.ru) */
final readonly class SmsPilotSender implements SmsSenderInterface
{
    public function __construct(
        private string $apiKey,
        private LoggerInterface $logger,
    ) {
    }

    public function send(string $phone, string $message): bool
    {
        $url = 'https://smspilot.ru/api.php';
        $params = [
            'send' => $message,
            'to' => $phone,
            'apikey' => $this->apiKey,
            'format' => 'json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            $this->logger->error('SMS API error', [
                'phone' => $phone,
                'http_code' => $httpCode,
                'response' => $response,
            ]);
            return false;
        }

        $data = json_decode((string)$response, true);
        /** @var array<string, mixed> $data */
        $send = $data['send'] ?? [];

        $status = null;

        if (is_array($send) && isset($send[0]) && is_array($send[0])) {
            $status = $send[0]['status'] ?? null;
        }

        if ($status === 'OK' || $status === '0') {
            $this->logger->info('SMS sent successfully', [
                'phone' => $phone,
                'status' => $status,
            ]);
            return true;
        }

        $this->logger->error('SMS API failed', [
            'phone' => $phone,
            'status' => $status,
            'response' => $response,
        ]);
        return false;
    }
}
