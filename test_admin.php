<?php
require_once 'includes/db.php';

$email = 'admin@pastimes.com';
$password = 'admin123';

$stmt = $pdo->prepare("SELECT * FROM tblAdmin WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch();

echo "<h1>Admin Login Test</h1>";
echo "Email: " . $email . "<br>";
echo "Found in database: " . ($admin ? 'YES' : 'NO') . "<br>";

if ($admin) {
    echo "Stored hash: " . $admin['password_hash'] . "<br>";
    $verify = password_verify($password, $admin['password_hash']);
    echo "Password verify result: " . ($verify ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
    
    if ($verify) {
        echo "<h2 style='color:green'>Login would work!</h2>";
    } else {
        echo "<h2 style='color:red'>Hash doesn't match. Re-run the INSERT SQL.</h2>";
    }
} else {
    echo "<h2 style='color:red'>Admin not found in database. Run the INSERT SQL.</h2>";
}
?>