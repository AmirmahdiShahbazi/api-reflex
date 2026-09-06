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
| Find existing user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT id, username
     FROM users
     WHERE device_token = ?
     LIMIT 1'
);

$stmt->execute([$deviceToken]);

$user = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| Existing user
|--------------------------------------------------------------------------
*/

if ($user) {
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int) $user['id'],
            'username' => $user['username'],
        ],
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Create new user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'INSERT INTO users (username, device_token)
     VALUES (?, ?)'
);

/*
 * We temporarily use a placeholder username.
 * After the INSERT, we know the user's ID,
 * so we can change it to "Player #ID".
 */

$stmt->execute([
    'Player',
    $deviceToken,
]);

$userId = (int) $pdo->lastInsertId();

$username = 'Player #' . $userId;

$stmt = $pdo->prepare(
    'UPDATE users
     SET username = ?
     WHERE id = ?'
);

$stmt->execute([
    $username,
    $userId,
]);

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $userId,
        'username' => $username,
    ],
]);