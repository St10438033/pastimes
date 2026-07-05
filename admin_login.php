<?php
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM tblAdmin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['user_type'] = 'admin';
        header('Location: admin_dashboard.php');
        exit();
    } else {
        $error = 'Invalid admin credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login — Pastimes</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="index.php" class="logo">pastimes<span>.</span></a>
        </div>
    </div>
</header>

<main class="container">
    <div style="max-width: 400px; margin: 80px auto;">
        <h1>Admin access</h1>
        <p style="color: var(--text-muted); margin-bottom: 32px;">Sign in to manage the store</p>
        
        <?php if($error): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 24px;"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>
    </div>
</main>

</body>
</html>