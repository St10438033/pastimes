<?php
require_once 'includes/db.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "<br>";
echo "New Hash: " . $hash . "<br><br>";

// Delete old admin
$pdo->exec("DELETE FROM tblAdmin WHERE email = 'admin@pastimes.com'");

// Insert with new hash
$stmt = $pdo->prepare("INSERT INTO tblAdmin (full_name, email, password_hash, is_super_admin) VALUES (?, ?, ?, ?)");
$stmt->execute(['Admin User', 'admin@pastimes.com', $hash, 1]);

echo "✅ Admin account created!<br>";
echo "Email: admin@pastimes.com<br>";
echo "Password: admin123<br>";

// Verify it works
$verify = password_verify('admin123', $hash);
echo "Verification test: " . ($verify ? '✅ SUCCESS' : '❌ FAILED');
?>