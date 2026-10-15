<?php
/**
 * Роутер для PHP built-in server
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Статические файлы — отдаём напрямую (только файлы, не директории)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
    return false;
}

// Всё остальное — на index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
return false;
