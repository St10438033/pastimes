<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
$user = getCurrentUser();

$success = '';
$error = '';

// Handle seller request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_seller'])) {
    $phone = $_POST['phone'] ?? '';
    $motivation = $_POST['motivation'] ?? '';
    
    // Check if already requested
    if (hasPendingSellerRequest($_SESSION['user_id'])) {
        $error = 'You already have a pending seller request.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO seller_requests (user_id, full_name, email, phone, motivation) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $user['full_name'], $user['email'], $phone, $motivation]);
        
        $stmt = $pdo->prepare("UPDATE tblUser SET seller_requested_at = NOW() WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $success = 'Request sent to admin. You\'ll be notified when approved.';
    }
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $address = $_POST['address'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $phone = $_POST['phone'];
    
    $stmt = $pdo->prepare("UPDATE tblUser SET address = ?, city = ?, province = ?, phone = ? WHERE user_id = ?");
    $stmt->execute([$address, $city, $province, $phone, $_SESSION['user_id']]);
    $user = getCurrentUser();
    $success = 'Profile updated';
}

// Get order history
$stmt = $pdo->prepare("SELECT * FROM tblOrder WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get seller status
$is_seller_approved = isUserSellerApproved($_SESSION['user_id']);
$has_requested = $user['seller_requested_at'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile — Pastimes</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="index.php" class="logo">pastimes<span>.</span></a>
            <nav class="nav">
                <div class="icons">
                    <a href="shop.php" class="icon-link">Shop</a>
                    <?php if($is_seller_approved): ?>
                        <a href="closet.php" class="icon-link">Sell</a>
                    <?php endif; ?>
                    <a href="logout.php" class="icon-link">Logout</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div style="max-width: 800px; margin: 48px auto;">

        <?php if(isset($_GET['error']) && $_GET['error'] == 'not_seller'): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border-left: 4px solid #dc2626;">
                <strong>⚠️ Seller Access Required</strong>
                <p style="margin-top: 4px; font-size: 0.9rem;">You need to be an approved seller to access the Closet. Request seller status below.</p>
            </div>
        <?php endif; ?>

        
        <h1>Profile</h1>
        <p>Welcome back, <?= htmlspecialchars($user['full_name']) ?></p>
        
        <div style="background: var(--bg-secondary); padding: 24px; border-radius: 16px; margin: 24px 0;">
            <h3>Wallet balance</h3>
            <p style="font-size: 2rem; font-weight: 500; color: var(--accent); margin-top: 8px;"><?= formatPrice($user['wallet_balance']) ?></p>
        </div>
        
        <!-- SELLER STATUS SECTION -->
        <div style="background: var(--bg-secondary); padding: 24px; border-radius: 16px; margin: 24px 0;">
            <h3>Seller Status</h3>
            <?php if ($is_seller_approved): ?>
                <p style="color: var(--accent);">✅ You are an approved seller! <a href="closet.php">Go to your closet →</a></p>
            <?php elseif ($has_requested): ?>
                <p style="color: #c46b2b;"> Your seller request is pending admin approval.</p>
                <p style="font-size: 0.8rem; color: var(--text-muted);">We'll notify you via email once reviewed.</p>
            <?php else: ?>
                <p>Want to sell items on Pastimes?</p>
                <button onclick="document.getElementById('sellerRequestForm').style.display='block'" class="btn btn-primary">Request to be a seller</button>
                
                <div id="sellerRequestForm" style="display: none; margin-top: 16px;">
                    <form method="POST">
                        <div class="form-group">
                            <label>Phone number</label>
                            <input type="text" name="phone" placeholder="071 234 5678">
                        </div>
                        <div class="form-group">
                            <label>Why do you want to sell? (optional)</label>
                            <textarea name="motivation" rows="3" placeholder="Tell us about your style, what you plan to sell..."></textarea>
                        </div>
                        <?php if($error): ?>
                            <div style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 16px;"><?= $error ?></div>
                        <?php endif; ?>
                        <?php if($success): ?>
                            <div style="background: #eef4ee; color: var(--accent); padding: 12px; border-radius: 12px; margin-bottom: 16px;"><?= $success ?></div>
                        <?php endif; ?>
                        <button type="submit" name="request_seller" class="btn btn-primary">Submit Request</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if($success && !isset($_POST['request_seller'])): ?>
            <div style="background: #eef4ee; color: var(--accent); padding: 12px; border-radius: 12px; margin-bottom: 24px;">✓ <?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <h3>Personal information</h3>
            <div class="form-group">
                <label>Full name</label>
                <input type="text" value="<?= htmlspecialchars($user['full_name']) ?>" disabled style="background: var(--bg-secondary);">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: var(--bg-secondary);">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Province</label>
                <input type="text" name="province" value="<?= htmlspecialchars($user['province'] ?? '') ?>">
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">Update profile</button>
        </form>
        
        <h3 style="margin-top: 48px;">Order history</h3>
        <?php if(empty($orders)): ?>
            <p style="color: var(--text-muted);">No orders yet</p>
        <?php else: ?>
            <?php foreach($orders as $order): ?>
                <div style="background: var(--bg-secondary); border-radius: 12px; padding: 20px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="font-weight: 500;">Order #<?= $order['order_id'] ?></span>
                        <span style="color: var(--accent);"><?= formatPrice($order['total_amount']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted);">
                        <span><?= date('d M Y', strtotime($order['order_date'])) ?></span>
                        <span><?= ucfirst($order['status']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

</body>
</html>