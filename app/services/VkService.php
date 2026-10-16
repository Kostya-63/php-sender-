<?php

namespace App\Services;

/**
 * Отправка PDF через VK Bot API
 */
class VkService extends MessengerService
{
    private string $apiUrl = 'https://api.vk.com/method';
    private string $version = '5.131';

    public function send(string $pdfContent, string $filename, array $config): MessengerResult
    {
        try {
            // chat_id берётся из config
            $chatId = $config['chat_id'] ?? null;
            if (!$chatId) {
                return MessengerResult::error('Не указан chat_id в config/messengers.php');
            }
            
            // Шаг 1: Получить upload server URL
            $uploadServerUrl = $this->getUploadServerUrl($config, $chatId);
            
            // Шаг 2: Загрузить PDF на сервер
            $response = $this->uploadFile($uploadServerUrl, $pdfContent, $filename);
            
            // Шаг 3: Сохранить документ
            $fileData = json_decode($response, true);
            if (!$fileData || !isset($fileData['file_data'])) {
                return MessengerResult::error('Не удалось сохранить документ в VK');
            }
            
            // Шаг 4: Отправить сообщение с документом
            $result = $this->sendMessage($config, $chatId, $fileData['file_data'], $filename);
            
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
        return !empty($this->config['token']);
    }

    private function getUploadServerUrl(array $config, int $chatId): string
    {
        $url = "{$this->apiUrl}/messages.getUploadServer?type=doc&v={$this->version}";
        $url .= "&chat_id={$chatId}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: oauth ' . $config['token']],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Ошибка получения upload server: ' . $response);
        }
        
        $data = json_decode($response, true);
        if (!isset($data['response']['upload_url'])) {
            throw new \Exception('Не получен upload_url от VK');
        }
        
        return $data['response']['upload_url'];
    }

    private function uploadFile(string $url, string $pdfContent, string $filename): string
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
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Ошибка загрузки файла в VK: ' . $response);
        }
        
        return $response;
    }

    private function sendMessage(array $config, int $chatId, string $fileData, string $filename): array
    {
        $url = "{$this->apiUrl}/messages.send?chat_id={$chatId}&v={$this->version}";
        $url .= "&attachment=doc" . $fileData;
        $url .= "&message=" . urlencode("Отчёт: {$filename}");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: oauth ' . $config['token']],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if ($httpCode !== 200 || !isset($data['response'])) {
            return ['success' => false, 'error' => $data['error']['error_msg'] ?? 'Ошибка отправки'];
        }
        
        return ['success' => true];
    }
}
