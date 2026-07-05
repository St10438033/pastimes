<?php
require_once 'includes/db.php';

$email = 'lerato@pastimes.com';
$password = 'password123';

$stmt = $pdo->prepare("SELECT * FROM tblUser WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

echo "<h1>Login Test</h1>";
echo "Email: " . $email . "<br>";
echo "Found: " . ($user ? 'YES' : 'NO') . "<br>";

if ($user) {
    echo "Stored hash: " . $user['password_hash'] . "<br>";
    $verify = password_verify($password, $user['password_hash']);
    echo "Password verify: " . ($verify ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
    
    if ($verify) {
        echo "<h2 style='color:green'>Login will work!</h2>";
    } else {
        echo "<h2 style='color:red'>Hash mismatch. Run the UPDATE SQL above.</h2>";
    }
}
?>