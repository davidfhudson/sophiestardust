<?php
// ONE-TIME MIGRATION SCRIPT — DELETE FROM SERVER AFTER USE
// Scores are hardcoded (fetched from JSONbin externally) — no outbound requests needed.

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/_db.php';

$attempts = [
    ["name"=>"The SOPHIE","quizId"=>"books-general","categoryId"=>"books","category"=>"Books","quiz"=>"General Book Knowledge","score"=>14,"total"=>15,"date"=>"2026-04-07T12:41:19.937Z"],
    ["name"=>"Jenn","quizId"=>"theatre-general","categoryId"=>"musical-theatre","category"=>"Musical Theatre","quiz"=>"West End & Broadway","score"=>14,"total"=>15,"date"=>"2026-04-07T12:43:19.293Z"],
    ["name"=>"The SOPHIE","quizId"=>"theatre-general","categoryId"=>"musical-theatre","category"=>"Musical Theatre","quiz"=>"West End & Broadway","score"=>14,"total"=>15,"date"=>"2026-04-07T12:44:08.436Z"],
    ["name"=>"DavidRules75","quizId"=>"movies-general","categoryId"=>"movies","category"=>"Movies","quiz"=>"Movie Magic","score"=>15,"total"=>15,"date"=>"2026-04-07T12:44:55.321Z"],
    ["name"=>"Jenn","quizId"=>"movies-general","categoryId"=>"movies","category"=>"Movies","quiz"=>"Movie Magic","score"=>14,"total"=>15,"date"=>"2026-04-07T12:45:09.270Z"],
    ["name"=>"Joseph","quizId"=>"movies-general","categoryId"=>"movies","category"=>"Movies","quiz"=>"Movie Magic","score"=>9,"total"=>15,"date"=>"2026-04-07T12:46:21.614Z"],
    ["name"=>"Jenn","quizId"=>"books-general","categoryId"=>"books","category"=>"Books","quiz"=>"General Book Knowledge","score"=>9,"total"=>15,"date"=>"2026-04-07T12:46:35.637Z"],
    ["name"=>"DavidRocks","quizId"=>"books-harry-potter-i","categoryId"=>"books","category"=>"Books","quiz"=>"Harry Potter I","score"=>7,"total"=>15,"date"=>"2026-04-07T14:14:20.109Z"],
    ["name"=>"QueenKatherine","quizId"=>"movies-general","categoryId"=>"movies","category"=>"Movies","quiz"=>"Movie Magic","score"=>12,"total"=>15,"date"=>"2026-04-07T14:19:03.578Z"],
    ["name"=>"The Creator","quizId"=>"books-random","categoryId"=>"books","category"=>"Books","quiz"=>"Books — Random Mix","score"=>14,"total"=>15,"date"=>"2026-04-07T15:06:57.247Z"],
    ["name"=>"The Creator","quizId"=>"books-harry-potter-i","categoryId"=>"books","category"=>"Books","quiz"=>"Harry Potter I","score"=>15,"total"=>15,"date"=>"2026-04-07T15:55:08.166Z"],
    ["name"=>"Clemmo","quizId"=>"books-general","categoryId"=>"books","category"=>"Books","quiz"=>"General Book Knowledge","score"=>10,"total"=>15,"date"=>"2026-04-08T14:20:44.932Z"],
    ["name"=>"The SOPHIE","quizId"=>"books-skandar","categoryId"=>"books","category"=>"Books","quiz"=>"Skandar Series","score"=>14,"total"=>15,"date"=>"2026-04-08T14:21:54.354Z"],
    ["name"=>"The SOPHIE","quizId"=>"movies-star-wars","categoryId"=>"movies","category"=>"Movies","quiz"=>"Star Wars Saga","score"=>6,"total"=>15,"date"=>"2026-04-08T14:27:18.040Z"],
    ["name"=>"Seren Sauls","quizId"=>"movies-general","categoryId"=>"movies","category"=>"Movies","quiz"=>"Movie Magic","score"=>13,"total"=>15,"date"=>"2026-04-08T18:41:04.076Z"],
    ["name"=>"Lilli.schanznig","quizId"=>"theatre-general","categoryId"=>"musical-theatre","category"=>"Musical Theatre","quiz"=>"West End & Broadway","score"=>6,"total"=>15,"date"=>"2026-04-09T05:14:12.137Z"],
    ["name"=>"Isabella R","quizId"=>"movies-general","categoryId"=>"movies","category"=>"Movies","quiz"=>"Movie Magic","score"=>15,"total"=>15,"date"=>"2026-04-09T07:19:28.204Z"],
    ["name"=>"Giselle","quizId"=>"movies-general","categoryId"=>"movies","category"=>"Movies","quiz"=>"Movie Magic","score"=>9,"total"=>15,"date"=>"2026-04-09T09:20:59.357Z"],
    ["name"=>"Giselle","quizId"=>"theatre-general","categoryId"=>"musical-theatre","category"=>"Musical Theatre","quiz"=>"West End & Broadway","score"=>11,"total"=>15,"date"=>"2026-04-09T09:24:17.655Z"],
    ["name"=>"Giselle","quizId"=>"books-general","categoryId"=>"books","category"=>"Books","quiz"=>"General Book Knowledge","score"=>15,"total"=>15,"date"=>"2026-04-09T09:40:35.163Z"],
    ["name"=>"The SOPHIE","quizId"=>"books-harry-potter-i","categoryId"=>"books","category"=>"Books","quiz"=>"Harry Potter I","score"=>14,"total"=>15,"date"=>"2026-04-12T07:31:07.441Z"],
    ["name"=>"The SOPHIE","quizId"=>"musical-theatre-hamilton","categoryId"=>"musical-theatre","category"=>"Musical Theatre","quiz"=>"Hamilton","score"=>15,"total"=>15,"date"=>"2026-04-14T13:27:28.374Z"],
];

echo "<pre>\n";
echo "Importing " . count($attempts) . " attempts...\n\n";

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
    $name = sanitise_name($a['name']);
    $check->execute([$name, $a['quizId']]);
    if ((int) $check->fetchColumn() > 0) {
        echo "SKIP (already exists): {$name} — {$a['quizId']}\n";
        $skipped++;
        continue;
    }
    $date = date('Y-m-d H:i:s', strtotime($a['date']));
    $insert->execute([$name, $a['quizId'], $a['categoryId'], $a['category'], $a['quiz'], $a['score'], $a['total'], $date]);
    echo "IMPORTED: {$name} — {$a['quizId']} ({$a['score']}/{$a['total']})\n";
    $imported++;
}

echo "\nDone. Imported: {$imported}, Skipped: {$skipped}\n";
echo "\n*** DELETE THIS FILE FROM THE SERVER NOW ***\n";
echo "</pre>\n";
