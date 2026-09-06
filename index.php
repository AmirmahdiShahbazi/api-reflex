<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Handle CORS preflight
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