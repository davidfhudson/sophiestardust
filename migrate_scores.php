<?php
// ONE-TIME MIGRATION SCRIPT
// Fetches all existing scores from JSONbin and imports them into MySQL.
//
// HOW TO USE:
//   1. Upload this file to public_html/sophiestardust.net/migrate_scores.php
//   2. Visit https://davidhudson.net/sophiestardust.net/migrate_scores.php in your browser
//   3. Check the output — it will report how many scores were imported
//   4. DELETE this file from the server immediately afterwards

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/_db.php';

$BIN_ID  = '69d4ef36856a6821890999a4';
$API_KEY = '$2a$10$Dh7UI7IdkiwaYgEfcWLyauvI9sTngUNQa7NmyPhHn1IXKHIplKVVG';

echo "<pre>\n";
echo "Fetching scores from JSONbin...\n";

$ch = curl_init("https://api.jsonbin.io/v3/b/{$BIN_ID}/latest");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["X-Master-Key: {$API_KEY}"],
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo "ERROR: Could not fetch from JSONbin (HTTP $httpCode)\n";
    exit;
}

$data     = json_decode($response, true);
$attempts = $data['record']['attempts'] ?? [];
echo "Found " . count($attempts) . " attempts in JSONbin.\n\n";

$pdo = db();
$check = $pdo->prepare(
    'SELECT COUNT(*) FROM attempts WHERE LOWER(name) = LOWER(?) AND quiz_id = ?'
);
$insert = $pdo->prepare(
    'INSERT INTO attempts (name, quiz_id, category_id, category, quiz, score, total, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

$imported = 0;
$skipped  = 0;

foreach ($attempts as $a) {
    $name = sanitise_name($a['name'] ?? '');
    $quizId = $a['quizId'] ?? '';

    $check->execute([$name, $quizId]);
    if ((int) $check->fetchColumn() > 0) {
        echo "SKIP (already exists): {$name} — {$quizId}\n";
        $skipped++;
        continue;
    }

    $date = $a['date'] ?? date('Y-m-d H:i:s');
    // Convert ISO 8601 to MySQL datetime
    $date = date('Y-m-d H:i:s', strtotime($date));

    $insert->execute([
        $name,
        $quizId,
        $a['categoryId'] ?? '',
        $a['category']   ?? '',
        $a['quiz']       ?? '',
        (int) ($a['score'] ?? 0),
        (int) ($a['total'] ?? 0),
        $date,
    ]);
    echo "IMPORTED: {$name} — {$quizId} ({$a['score']}/{$a['total']})\n";
    $imported++;
}

echo "\nDone. Imported: {$imported}, Skipped (already in DB): {$skipped}\n";
echo "\n*** DELETE THIS FILE FROM THE SERVER NOW ***\n";
echo "</pre>\n";
