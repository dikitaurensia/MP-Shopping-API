<?php
// router.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Kalau ada file atau folder asli, biarkan server handle
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Hilangkan slash depan
$path = ltrim($uri, '/');

// Cek kalau ada file .php yang sesuai (contoh: /api/products → api/products.php)
if ($path && file_exists(__DIR__ . '/' . $path . '.php')) {
    require __DIR__ . '/' . $path . '.php';
    return true;
}

// Fallback ke index.php (misal untuk routing custom)
require __DIR__ . '/index.php';
