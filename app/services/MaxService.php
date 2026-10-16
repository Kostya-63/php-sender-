<?php

namespace App\Services;

/**
 * Отправка PDF через Max API
 */
class MaxService extends MessengerService
{
    private int $maxRetries = 3;
    private int $baseDelay = 2; // секунды

    public function send(string $pdfContent, string $filename, array $config): MessengerResult
    {
        try {
            // user_id берётся из config
            $userId = $config['user_id'] ?? null;
            if (!$userId) {
                return MessengerResult::error('Не указан user_id в config/messengers.php');
            }
            
            // Шаг 1: Получить upload URL и token
            [$uploadUrl, $token] = $this->getUploadUrl($config);
            
            // Шаг 2: Загрузить файл
            $this->uploadFile($uploadUrl, $pdfContent, $filename);
            
            // Шаг 3: Отправить сообщение с вложением (с retry-логикой)
            $result = $this->sendMessageWithRetry($config, $token, $userId, $filename);
            
            if ($result['success']) {
                return MessengerResult::success("PDF отправлен в Max");
            }
            
            return MessengerResult::error($result['error'] ?? 'Ошибка отправки');
            
        } catch (\Exception $e) {
            return MessengerResult::error($e->getMessage());
        }
    }

    public function isEnabled(): bool
    {
        return !empty($this->config['token']);
    }

    private function getUploadUrl(array $config): array
    {
        $url = $config['api_url'] . '/uploads?type=file';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $config['token'],
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Ошибка получения upload URL от Max: ' . $response);
        }
        
        $data = json_decode($response, true);
        if (!isset($data['url']) || !isset($data['token'])) {
            throw new \Exception('Не получен upload URL или token от Max');
        }
        
        return [$data['url'], $data['token']];
    }

    private function uploadFile(string $url, string $pdfContent, string $filename): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->config['token'],
            ],
            CURLOPT_POSTFIELDS => [
                'data' => new \CURLStringFile($pdfContent, $filename, 'application/pdf'),
            ],
            CURLOPT_TIMEOUT => 60,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Ошибка загрузки файла в Max: ' . $response);
        }
    }

    private function sendMessageWithRetry(array $config, string $token, int $userId, string $filename): array
    {
        $url = $config['api_url'] . "/messages?user_id={$userId}";
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            // Пауза перед отправкой (после загрузки файла)
            if ($attempt > 1) {
                $delay = $this->baseDelay * $attempt;
                sleep($delay);
            }
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $config['token'],
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'text' => "Отчёт: {$filename}",
                    'attachments' => [
                        [
                            'type' => 'file',
                            'payload' => [
                                'token' => $token,
                            ],
                        ],
                    ],
                ]),
                CURLOPT_TIMEOUT => 30,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Успех
            if ($httpCode === 200) {
                return ['success' => true];
            }
            
            $data = json_decode($response, true);
            $errorMsg = $data['message'] ?? 'Ошибка отправки';
            
            // Если ошибка "attachment.not.ready" — повторяем
            if (strpos($errorMsg, 'not.ready') !== false && $attempt < $this->maxRetries) {
                continue;
            }
            
            // Иная ошибка — возвращаем
            return ['success' => false, 'error' => $errorMsg];
        }
        
        return ['success' => false, 'error' => 'Не удалось отправить после ' . $this->maxRetries . ' попыток'];
    }
}
