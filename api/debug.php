<?php
// TEMPORARY DIAGNOSTIC — DELETE FROM SERVER AFTER USE
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>\n";

// Check config loads
echo "1. Loading config.php... ";
if (!file_exists(__DIR__ . '/config.php')) {
    echo "MISSING — api/config.php not found on server\n";
} else {
    require_once __DIR__ . '/config.php';
    echo "OK\n";
    echo "   DB_HOST: " . DB_HOST . "\n";
    echo "   DB_NAME: " . DB_NAME . "\n";
    echo "   DB_USER: " . DB_USER . "\n";
}

// Check DB connection
echo "\n2. Connecting to MySQL... ";
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "OK\n";
} catch (PDOException $e) {
    echo "FAILED\n   Error: " . $e->getMessage() . "\n";
    exit;
}

// Check tables exist
echo "\n3. Checking tables... ";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "\n   Tables found: " . implode(', ', $tables) . "\n";
echo "   'attempts' table: " . (in_array('attempts', $tables) ? "EXISTS" : "MISSING — run sql/schema.sql") . "\n";
echo "   'quizzes' table:  " . (in_array('quizzes',  $tables) ? "EXISTS" : "MISSING — run sql/schema.sql") . "\n";

echo "\n*** DELETE api/debug.php FROM SERVER NOW ***\n";
echo "</pre>\n";
