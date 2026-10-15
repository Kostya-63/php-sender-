<?php

namespace App\Controllers;

use App\Services\PdfService;

class PdfController
{
    /**
     * Создаёт PDF и возвращает данные
     *
     * @return array ['success' => bool, 'data' => string|null, 'error' => string|null]
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
     * Отправляет PDF в мессенджер (заглушка)
     *
     * @return array ['success' => bool, 'message' => string, 'error' => string|null]
     */
    public function sendToMessenger(): array
    {
        try {
            $messengerService = new \App\Services\MessengerService();
            $result = $messengerService->send();

            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка отправки',
                'error' => $e->getMessage(),
            ];
        }
    }
}
