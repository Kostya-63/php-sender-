<?php

namespace App\Services;

/**
 * Заглушка для отправки в мессенджер
 * На втором этапе будет реализована интеграция с конкретным мессенджером
 */
class MessengerService
{
    /**
     * Отправляет PDF в мессенджер
     *
     * @return array ['success' => bool, 'message' => string, 'error' => string|null]
     */
    public function send(): array
    {
        // Заглушка: логирование + возврат статуса
        error_log('[MessengerService] Отправка PDF в мессенджер (заглушка)');

        return [
            'success' => true,
            'message' => 'PDF успешно отправлен в мессенджер',
            'error' => null,
        ];
    }
}
