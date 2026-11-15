<?php
require_once __DIR__ . '/vendor/autoload.php';
\App\Core\Config::load(__DIR__);
$pdo = \App\Core\DB::pdo();

$newPass = 'Msantha2026!';
$hash = password_hash($newPass, PASSWORD_ARGON2ID);
if (!$hash) {
    $hash = password_hash($newPass, PASSWORD_BCRYPT);
}

$stmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE username = 'ik.chipeta'");
$stmt->execute([$hash]);

echo "Updated ik.chipeta password successfully to: {$newPass}\n";

// Verify it
$u = $pdo->query("SELECT id, username, password_hash FROM users WHERE username = 'ik.chipeta'")->fetch(PDO::FETCH_ASSOC);
echo "Verification: " . (password_verify($newPass, $u['password_hash']) ? "MATCH" : "MISMATCH") . "\n";
