<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM tblUser WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_type'] = 'user';
        $_SESSION['wallet_balance'] = $user['wallet_balance'];
        
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
        } else {
            header('Location: index.php');
        }
        exit();
    } else {
        $error = 'Invalid email or password';
    }
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>Login — Pastimes</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="index.php" class="logo">pastimes<span>.</span></a>
            <nav class="nav">
                <div class="icons">
                    <a href="register.php" class="icon-link">Register</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div style="max-width: 400px; margin: 80px auto;">
        <h1 style="margin-bottom: 8px;">Welcome back</h1>
        <p style="color: var(--text-muted); margin-bottom: 32px;">Sign in to your account</p>
        
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
        
        <p style="margin-top: 24px; text-align: center; font-size: 0.8rem;">
            Don't have an account? <a href="register.php" style="color: var(--accent);">Create one</a>
        </p>
    </div>
</main>

</body>
</html>