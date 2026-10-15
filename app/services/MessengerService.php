<?php

namespace App\Services;

/**
 * Результат отправки
 */
class MessengerResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $error = null
    ) {}

    public static function success(string $message): self
    {
        return new self(true, $message);
    }

    public static function error(string $error): self
    {
        return new self(false, '', $error);
    }
}

/**
 * Базовый интерфейс для всех мессенджеров
 */
abstract class MessengerService
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Отправить PDF-документ
     *
     * @param string $pdfContent Бинарные данные PDF
     * @param string $filename Имя файла
     * @param mixed $recipient Данные получателя
     * @return MessengerResult
     */
    abstract public function send(string $pdfContent, string $filename, mixed $recipient): MessengerResult;

    /**
     * Проверить, включён ли мессенджер
     */
    abstract public function isEnabled(): bool;
}
