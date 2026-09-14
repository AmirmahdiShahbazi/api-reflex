<?php

// دریافت آدرس فرانت‌اند به‌صورت خودکار
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// لیست پورت‌های مجاز فرانت‌اند
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:5174',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:5174',
];

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} elseif (empty($origin)) {
    header("Access-Control-Allow-Origin: *");
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// پاسخ به درخواست‌های Preflight مرورگر (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$route = $_GET['route'] ?? '';

switch ($route) {

    case '':
        echo json_encode([
            'success' => true,
            'message' => 'Reflex Games API is running.'
        ]);
        break;

    case 'user':
        require __DIR__ . '/api/user.php';
        break;

    case 'username':
        require __DIR__ . '/api/username.php';
        break;

    case 'score':
        require __DIR__ . '/api/score.php';
        break;

    default:
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found.'
        ]);
        break;
}