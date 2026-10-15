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
     *
     * @return array ['success' => bool, 'message' => string, 'error' => string|null]
     */
    public function send(array $payload): array
    {
        $channel = $payload['channel'] ?? '';
        $recipient = $payload['recipient'] ?? [];
        
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
            $result = $service->send($pdfContent, $filename, $recipient);

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
     * Получить список получателей
     */
    public function getRecipients(): array
    {
        // TODO: Загрузить из БД портала
        // Пока mock-данные для демонстрации
        return [
            [
                'id' => 1,
                'name' => 'Иванов А.А.',
                'role' => 'Преподаватель',
                'department' => 'Математика',
                'email' => 'ivanov@university.ru',
                'vk_chat_id' => 1,
                'max_user_id' => 123456,
            ],
            [
                'id' => 2,
                'name' => 'Петрова В.Г.',
                'role' => 'Преподаватель',
                'department' => 'Физика',
                'email' => 'petrova@university.ru',
                'vk_chat_id' => 2,
                'max_user_id' => 789012,
            ],
            [
                'id' => 3,
                'name' => 'Сидоров К.М.',
                'role' => 'Куратор',
                'department' => 'Кафедра ИТ',
                'email' => 'sidorov@university.ru',
                'vk_chat_id' => 3,
                'max_user_id' => 345678,
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

    private function generatePdfContent(): ?string
    {
        try {
            $pdfService = new PdfService();
            return $pdfService->generate();
        } catch (\Exception $e) {
            return null;
        }
    }
}
