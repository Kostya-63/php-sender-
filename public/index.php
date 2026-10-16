<?php

/**
 * Точка входа приложения
 * Роутинг и обработка запросов
 */

// Включаем отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Загрузка автозагрузчика Composer
require_once __DIR__ . '/../vendor/autoload.php';

// CORS для AJAX-запросов
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Обработка OPTIONS запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Роутинг
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($path) {
        case '/':
        case '/index.php':
            http_response_code(200);
            header('Content-Type: text/html; charset=utf-8');
            require_once __DIR__ . '/../app/views/index.php';
            exit;

        case '/api/create-pdf':
            if ($method === 'POST') {
                require_once __DIR__ . '/../app/controllers/PdfController.php';
                $controller = new \App\Controllers\PdfController();
                echo json_encode($controller->createPdf(), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case '/api/send':
            if ($method === 'POST') {
                require_once __DIR__ . '/../app/controllers/PdfController.php';
                $controller = new \App\Controllers\PdfController();
                $input = json_decode(file_get_contents('php://input'), true) ?? [];
                echo json_encode($controller->send($input), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case '/api/recipients':
            if ($method === 'GET') {
                require_once __DIR__ . '/../app/controllers/PdfController.php';
                $controller = new \App\Controllers\PdfController();
                echo json_encode($controller->getRecipients(), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            break;
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
