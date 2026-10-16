<?php

namespace App\Services;

/**
 * Отправка PDF через VK Bot API
 */
class VkService extends MessengerService
{
    private string $apiUrl = 'https://api.vk.com/method';
    private string $version = '5.131';

    public function send(string $pdfContent, string $filename, mixed $recipient): MessengerResult
    {
        try {
            // Шаг 1: Получить upload server URL
            $uploadServerUrl = $this->getUploadServerUrl();
            
            // Шаг 2: Загрузить PDF на сервер
            $response = $this->uploadFile($uploadServerUrl, $pdfContent, $filename);
            
            // Шаг 3: Сохранить документ
            $fileData = json_decode($response, true);
            if (!$fileData || !isset($fileData['file_data'])) {
                return MessengerResult::error('Не удалось сохранить документ в VK');
            }
            
            // Шаг 4: Отправить сообщение с документом
            $chatId = (string) $recipient;
            $result = $this->sendMessage($chatId, $fileData['file_data'], $filename);
            
            if ($result['success']) {
                return MessengerResult::success("PDF отправлен в VK (chat_id: {$chatId})");
            }
            
            return MessengerResult::error($result['error'] ?? 'Ошибка отправки');
            
        } catch (\Exception $e) {
            return MessengerResult::error($e->getMessage());
        }
    }

    public function isEnabled(): bool
    {
        return (!empty($this->config['enabled']) && $this->config['enabled']) &&
               !empty($this->config['token']);
    }

    private function apiCall(string $method, array $params): array
    {
        $url = "{$this->apiUrl}/{$method}?v={$this->version}&access_token=" . $this->config['token'];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . '&' . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        if ($response === false || $curlErrno !== 0) {
            throw new \Exception("cURL ошибка (#{$curlErrno}): {$curlError}");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP {$httpCode}: " . substr($response, 0, 500));
        }
        
        if (empty(trim($response))) {
            throw new \Exception("VK API вернул пустой ответ. URL: " . preg_replace('/access_token=[^&]*/', 'access_token=***', $url . '&' . http_build_query($params)));
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg() . ' | Response: ' . $response);
        }
        
        if (isset($data['error_code'], $data['error_msg'])) {
            throw new \Exception("VK API error {$data['error_code']}: {$data['error_msg']}");
        }
        
        return $data['response'] ?? [];
    }

    private function getUploadServerUrl(): string
    {
        $params = ['type' => 'doc'];
        $chatId = $this->config['chat_id'] ?? '';
        if ($chatId) {
            // Для групп используем group_id (без минуса)
            $params['group_id'] = abs((int) $chatId);
        }
        
        $response = $this->apiCall('messages.getUploadServer', $params);
        
        if (empty($response)) {
            throw new \Exception('VK вернул пустой ответ. Params: ' . json_encode($params));
        }
        
        if (!isset($response['upload_url'])) {
            throw new \Exception('Не получен upload_url от VK. Полный ответ: ' . json_encode($response));
        }
        
        return $response['upload_url'];
    }

    private function uploadFile(string $url, string $pdfContent, string $filename): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => new \CURLStringFile($pdfContent, $filename, 'application/pdf'),
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        if ($response === false || $curlErrno !== 0) {
            throw new \Exception("cURL ошибка (#{$curlErrno}): {$curlError}");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP {$httpCode}: " . substr($response, 0, 500));
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }
        
        return $data;
    }

    private function sendMessage(string $chatId, string $fileData, string $filename): array
    {
        $params = [
            'chat_id' => $chatId,
            'attachment' => 'doc' . $fileData,
            'message' => 'Отчёт: ' . $filename,
        ];
        
        $response = $this->apiCall('messages.send', $params);
        
        return ['success' => true];
    }
}
