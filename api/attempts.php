<?php
// GET  /api/attempts.php        — returns all attempts as { "attempts": [...] }
// POST /api/attempts.php        — submits a new attempt (server enforces first-attempt rule)
//                                 returns { "ok": true, "firstAttempt": true|false }

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
    exit;
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['fatal' => $err['message'], 'file' => basename($err['file']), 'line' => $err['line']]);
    }
});

require_once __DIR__ . '/_db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = db()->query(
        'SELECT name, quiz_id AS quizId, category_id AS categoryId,
                category, quiz, score, total,
                DATE_FORMAT(created_at, "%Y-%m-%dT%H:%i:%sZ") AS date
         FROM attempts
         ORDER BY created_at ASC'
    )->fetchAll();

    json_response(['attempts' => $rows]);
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) json_response(['error' => 'Invalid JSON'], 400);

    $name       = sanitise_name($body['name']       ?? '');
    $quizId     = trim($body['quizId']     ?? '');
    $categoryId = trim($body['categoryId'] ?? '');
    $category   = trim($body['category']   ?? '');
    $quiz       = trim($body['quiz']       ?? '');
    $score      = (int) ($body['score']    ?? -1);
    $total      = (int) ($body['total']    ?? -1);

    if ($quizId === '' || $score < 0 || $total <= 0) {
        json_response(['error' => 'Missing required fields'], 400);
    }

    $pdo = db();

    // First-attempt check (case-insensitive name match)
    $check = $pdo->prepare(
        'SELECT COUNT(*) FROM attempts WHERE LOWER(name) = LOWER(?) AND quiz_id = ?'
    );
    $check->execute([$name, $quizId]);
    if ((int) $check->fetchColumn() > 0) {
        json_response(['ok' => true, 'firstAttempt' => false]);
    }

    $insert = $pdo->prepare(
        'INSERT INTO attempts (name, quiz_id, category_id, category, quiz, score, total)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([$name, $quizId, $categoryId, $category, $quiz, $score, $total]);

    json_response(['ok' => true, 'firstAttempt' => true]);
}

json_response(['error' => 'Method not allowed'], 405);
