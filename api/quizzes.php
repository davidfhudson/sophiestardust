<?php
// GET  /api/quizzes.php         — returns the quizzes JSON (from DB, falling back to static file)
// POST /api/quizzes.php         — saves quizzes JSON to DB (requires X-Admin-Password header)

require_once __DIR__ . '/_db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $row = db()->query('SELECT json_data FROM quizzes WHERE id = 1')->fetch();

    if ($row) {
        // Serve directly from DB
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo $row['json_data'];
        exit;
    }

    // DB not yet seeded — fall back to static file (initial migration only)
    $static = __DIR__ . '/../data/quizzes.json';
    if (file_exists($static)) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        readfile($static);
        exit;
    }

    json_response(['categories' => []]);
}

if ($method === 'POST') {
    // Verify admin password from header
    $supplied = $_SERVER['HTTP_X_ADMIN_PASSWORD'] ?? '';
    if ($supplied !== ADMIN_PASSWORD) {
        json_response(['error' => 'Unauthorised'], 401);
    }

    $body = file_get_contents('php://input');
    $parsed = json_decode($body, true);
    if (!$parsed || !isset($parsed['categories'])) {
        json_response(['error' => 'Invalid quiz JSON'], 400);
    }

    // Canonicalise back to clean JSON before storing
    $clean = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO quizzes (id, json_data) VALUES (1, ?)
         ON DUPLICATE KEY UPDATE json_data = VALUES(json_data), updated_at = NOW()'
    );
    $stmt->execute([$clean]);

    json_response(['ok' => true]);
}

json_response(['error' => 'Method not allowed'], 405);
