<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Read request
|--------------------------------------------------------------------------
*/

$input = json_decode(
    file_get_contents('php://input'),
    true
);

$deviceToken = $input['device_token'] ?? null;
$newUsername = $input['username'] ?? null;

/*
|--------------------------------------------------------------------------
| Validate device token
|--------------------------------------------------------------------------
*/

if (
    !is_string($deviceToken) ||
    strlen($deviceToken) < 16 ||
    strlen($deviceToken) > 128
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid device token.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Validate username
|--------------------------------------------------------------------------
*/

if (!is_string($newUsername)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Username is required.'
    ]);

    exit;
}

$newUsername = trim($newUsername);

if ($newUsername === '') {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Username cannot be empty.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Username length
|--------------------------------------------------------------------------
*/

if (mb_strlen($newUsername, 'UTF-8') < 2) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Username must be at least 2 characters.'
    ]);

    exit;
}

if (mb_strlen($newUsername, 'UTF-8') > 32) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Username cannot be longer than 32 characters.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Find user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT id
     FROM users
     WHERE device_token = ?
     LIMIT 1'
);

$stmt->execute([$deviceToken]);

$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'User not found.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Update username
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'UPDATE users
     SET username = ?
     WHERE id = ?'
);

$stmt->execute([
    $newUsername,
    $user['id'],
]);

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'user' => [
        'id' => (int) $user['id'],
        'username' => $newUsername,
    ],
]);