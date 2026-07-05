<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM tblUser WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO tblUser (full_name, email, password_hash, wallet_balance) VALUES (?, ?, ?, 100.00)");
            if ($stmt->execute([$full_name, $email, $password_hash])) {
                $success = 'Registration successful! Please login.';
            } else {
                $error = 'Registration failed';
            }
        }
    }
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>Register — Pastimes</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="index.php" class="logo">pastimes<span>.</span></a>
            <nav class="nav">
                <div class="icons">
                    <a href="login.php" class="icon-link">Login</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div style="max-width: 400px; margin: 80px auto;">
        <h1 style="margin-bottom: 8px;">Join Pastimes</h1>
        <p style="color: var(--text-muted); margin-bottom: 32px;">Create your account</p>
        
        <?php if($error): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 24px;"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div style="background: #eef4ee; color: var(--accent); padding: 12px; border-radius: 12px; margin-bottom: 24px;"><?= $success ?> <a href="login.php">Login here →</a></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Full name</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create account</button>
        </form>
        
        <p style="margin-top: 24px; text-align: center; font-size: 0.8rem;">
            Already have an account? <a href="login.php" style="color: var(--accent);">Sign in</a>
        </p>
    </div>
</main>

</body>
</html>