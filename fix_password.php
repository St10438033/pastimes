<?php
require_once 'includes/db.php';

$email = 'lerato@pastimes.com';
$password = 'password123';
$new_hash = password_hash($password, PASSWORD_DEFAULT);

echo "New hash for '$password': <br><code>$new_hash</code><br><br>";

$stmt = $pdo->prepare("UPDATE tblUser SET password_hash = ? WHERE email = ?");
$stmt->execute([$new_hash, $email]);

echo "✅ Password updated for $email<br>";

// Verify
$stmt = $pdo->prepare("SELECT password_hash FROM tblUser WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
$verify = password_verify($password, $user['password_hash']);

echo "Verification: " . ($verify ? '✅ SUCCESS' : '❌ FAILED');
?>