<?php

/**
 * Конфигурация мессенджеров
 * Заполните токены и настройки для реальной отправки
 */

return [
    // VK Bot API
    'vk' => [
        'enabled' => false,
        'token' => '',           // group access token
        'chat_id' => null,       // ID чата/группы для отправки
        'api_version' => '5.131',
    ],

    // Max API
    'max' => [
        'enabled' => false,
        'token' => '',           // bot token из business.max.ru
        'api_url' => 'https://platform-api2.max.ru',
        'user_id' => null,       // ID пользователя для отправки
    ],

    // Email (SMTP)
    'email' => [
        'enabled' => false,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_user' => '',       // email адрес
        'smtp_pass' => '',       // app password
        'from_email' => '',      // email отправителя
        'from_name' => 'PHP Sender',
    ],
];
