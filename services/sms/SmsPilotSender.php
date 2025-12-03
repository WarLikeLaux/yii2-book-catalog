<?php

declare(strict_types=1);

namespace app\services\sms;

use app\interfaces\SmsSenderInterface;
use Psr\Log\LoggerInterface;

final class SmsPilotSender implements SmsSenderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly LoggerInterface $logger
    ) {
    }

    public function send(string $phone, string $message): bool
    {
        if ($this->apiKey === 'MOCK_KEY') {
            $this->logger->info('SMS emulated', [
                'phone' => $phone,
                'message' => $message,
                'mode' => 'mock',
            ]);
            return true;
        }

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

        if ($httpCode !== 200 || !$response) {
            $this->logger->error('SMS API error', [
                'phone' => $phone,
                'http_code' => $httpCode,
                'response' => $response,
            ]);
            return false;
        }

        $data = json_decode($response, true);
        $status = $data['send'][0]['status'] ?? null;
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
