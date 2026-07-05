<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Force login for testing — replace with your user ID
// Find your user_id from Step 2
$user_id = 1; // CHANGE THIS to your actual user_id

echo "<h1>Testing isUserSellerApproved()</h1>";

// Check 1: Direct database query
$stmt = $pdo->prepare("SELECT is_seller_approved FROM tblUser WHERE user_id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch();

echo "Direct DB query result: ";
echo "<pre>";
print_r($result);
echo "</pre>";

// Check 2: Using the function
$is_approved = isUserSellerApproved($user_id);
echo "isUserSellerApproved() returns: " . ($is_approved ? "✅ TRUE (approved)" : "❌ FALSE (not approved)") . "<br>";

// Check 3: Test what happens when you access closet
echo "<br>Testing closet access:<br>";
if ($is_approved) {
    echo "✅ You can access closet.php";
} else {
    echo "❌ You would be redirected to profile.php?error=not_seller";
}

// Check 4: Check session user_id
echo "<br><br>Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET');
?>