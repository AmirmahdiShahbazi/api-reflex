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

$input = json_decode(
    file_get_contents('php://input'),
    true
);

$deviceToken = $input['device_token'] ?? null;
$game = $input['game'] ?? null;
$score = $input['score'] ?? null;


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
| Validate game
|--------------------------------------------------------------------------
*/

$allowedGames = [
    'reflex',
    'stack',
    'dodge',
    'flappy',
    '2048',
    'snake',
    'tetris',
];

if (
    !is_string($game) ||
    !in_array($game, $allowedGames, true)
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid game.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate score
|--------------------------------------------------------------------------
*/

if (
    !is_int($score) &&
    !is_numeric($score)
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid score.'
    ]);

    exit;
}

$score = (int) $score;

if ($score < 0) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Score cannot be negative.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Find user
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

if (!$user) {
    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'User not found.'
    ]);

    exit;
}

$userId = (int) $user['id'];


/*
|--------------------------------------------------------------------------
| Get user's previous best score for this game
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT MAX(score) AS best_score
     FROM scores
     WHERE user_id = ?
       AND game = ?'
);

$stmt->execute([
    $userId,
    $game,
]);

$result = $stmt->fetch();

$previousBest = $result['best_score'] !== null
    ? (int) $result['best_score']
    : 0;


/*
|--------------------------------------------------------------------------
| Determine whether this is a new record
|--------------------------------------------------------------------------
*/

$isNewRecord = $score > $previousBest;


/*
|--------------------------------------------------------------------------
| Save score
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'INSERT INTO scores (
        user_id,
        game,
        score
     )
     VALUES (?, ?, ?)'
);

$stmt->execute([
    $userId,
    $game,
    $score,
]);


/*
|--------------------------------------------------------------------------
| Get user's current record
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT MAX(score) AS record
     FROM scores
     WHERE user_id = ?
       AND game = ?'
);

$stmt->execute([
    $userId,
    $game,
]);

$result = $stmt->fetch();

$record = (int) $result['record'];


/*
|--------------------------------------------------------------------------
| Calculate player's position
|
| Ranking:
|   1. Higher score comes first
|   2. If scores are equal, lower user ID comes first
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT COUNT(*) + 1 AS position
     FROM (
         SELECT user_id, MAX(score) AS best_score
         FROM scores
         WHERE game = ?
         GROUP BY user_id
     ) leaderboard
     WHERE best_score > ?
        OR (
            best_score = ?
            AND user_id < ?
        )'
);

$stmt->execute([
    $game,
    $record,
    $record,
    $userId,
]);

$result = $stmt->fetch();

$position = (int) $result['position'];


/*
|--------------------------------------------------------------------------
| Get 3 players above + current player + 3 players below
|--------------------------------------------------------------------------
|
| OFFSET is zero-based, so:
|
| position 1 -> offset 0
| position 2 -> offset 0
| position 3 -> offset 0
| position 4 -> offset 0
| position 5 -> offset 1
|
*/

$offset = max(0, $position - 4);


/*
|--------------------------------------------------------------------------
| Get leaderboard
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        u.id,
        u.username,
        MAX(s.score) AS score
     FROM users u
     INNER JOIN scores s
        ON s.user_id = u.id
     WHERE s.game = ?
     GROUP BY u.id, u.username
     ORDER BY score DESC, u.id ASC
     LIMIT 7 OFFSET ?'
);

$stmt->execute([
    $game,
    $offset,
]);

$players = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Add positions and mark current player
|--------------------------------------------------------------------------
*/

$leaderboard = [];

foreach ($players as $index => $player) {

    $playerId = (int) $player['id'];

    $leaderboard[] = [
        'id' => $playerId,
        'username' => $player['username'],
        'score' => (int) $player['score'],
        'position' => $offset + $index + 1,
        'is_me' => $playerId === $userId,
    ];
}


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,

    'game' => $game,

    'score' => $score,

    'record' => $record,

    'is_new_record' => $isNewRecord,

    'position' => $position,

    'players' => $leaderboard,
]);