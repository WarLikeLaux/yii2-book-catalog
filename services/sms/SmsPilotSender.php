<?php

declare(strict_types=1);

namespace app\services\sms;

use app\interfaces\SmsSenderInterface;
use Yii;

final class SmsPilotSender implements SmsSenderInterface
{
    public function __construct(
        private readonly string $apiKey
    ) {}

    public function send(string $phone, string $message): bool
    {
        if ($this->apiKey === 'MOCK_KEY') {
            Yii::info("SMS emulated: {$phone} - {$message}", 'sms');
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
            Yii::error("SMS API error: HTTP {$httpCode}", 'sms');
            return false;
        }

        $data = json_decode($response, true);
        $status = $data['send'][0]['status'] ?? null;
        if ($status === 'OK' || $status === '0') {
            Yii::info("SMS sent: {$phone}", 'sms');
            return true;
        }

        Yii::error("SMS API failed: {$response}", 'sms');
        return false;
    }
}
