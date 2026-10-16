<?php

namespace App\Controllers;

use App\Services\PdfService;
use App\Services\MessengerResult;
use App\Services\VkService;
use App\Services\MaxService;
use App\Services\EmailService;

class PdfController
{
    private array $messengerConfig;

    public function __construct()
    {
        $this->messengerConfig = require __DIR__ . '/../../config/messengers.php';
    }

    /**
     * Создаёт PDF и возвращает данные
     */
    public function createPdf(): array
    {
        try {
            $pdfService = new PdfService();
            $pdfContent = $pdfService->generate();

            return [
                'success' => true,
                'data' => base64_encode($pdfContent),
                'error' => null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Отправляет PDF через выбранный мессенджер
     * Получатель берётся из config/messengers.php
     *
     * @return array ['success' => bool, 'message' => string, 'error' => string|null]
     */
    public function send(array $payload): array
    {
        $channel = $payload['channel'] ?? '';
        
        // Получаем PDF из сессии или генерируем заново
        $pdfBase64 = $payload['pdf_content'] ?? '';
        $filename = $payload['filename'] ?? 'отчёт.pdf';

        if (!$pdfBase64) {
            return ['success' => false, 'message' => '', 'error' => 'PDF не найден'];
        }

        // Декодируем base64 в бинарные данные
        $pdfContent = base64_decode($pdfBase64);
        if (!$pdfContent) {
            return ['success' => false, 'message' => '', 'error' => 'Ошибка декодирования PDF'];
        }

        $service = $this->getMessengerService($channel);
        
        if (!$service) {
            return ['success' => false, 'message' => '', 'error' => "Канал '{$channel}' не найден"];
        }

        if (!$service->isEnabled()) {
            return ['success' => false, 'message' => '', 'error' => "Канал '{$channel}' не настроен"];
        }

        try {
            $result = $service->send($pdfContent, $filename, $this->messengerConfig);

            return [
                'success' => $result->success,
                'message' => $result->message,
                'error' => $result->error,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить список доступных каналов
     */
    public function getChannels(): array
    {
        return [
            'vk' => [
                'name' => 'VK',
                'enabled' => $this->messengerConfig['vk']['enabled'] ?? false,
            ],
            'max' => [
                'name' => 'Max',
                'enabled' => $this->messengerConfig['max']['enabled'] ?? false,
            ],
            'email' => [
                'name' => 'Email',
                'enabled' => $this->messengerConfig['email']['enabled'] ?? false,
            ],
        ];
    }

    private function getMessengerService(string $channel): ?\App\Services\MessengerService
    {
        switch (strtolower($channel)) {
            case 'vk':
                return new VkService($this->messengerConfig['vk'] ?? []);
            case 'max':
                return new MaxService($this->messengerConfig['max'] ?? []);
            case 'email':
                return new EmailService($this->messengerConfig['email'] ?? []);
            default:
                return null;
        }
    }
}
