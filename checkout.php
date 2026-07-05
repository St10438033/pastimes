<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$user = getCurrentUser();
$cart_items = getCartItems();
$total = getCartTotal();
$shipping = calculateShipping($total);
$grand_total = $total + $shipping;

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_method = $_POST['delivery_method'] ?? 'courier_guy';
    $shipping_address = $_POST['shipping_address'] ?? $user['address'];
    
    if ($user['wallet_balance'] < $grand_total) {
        $error = "Insufficient wallet balance. Your balance: " . formatPrice($user['wallet_balance']);
    } else {
        // Deduct from wallet
        $new_balance = $user['wallet_balance'] - $grand_total;
        $stmt = $pdo->prepare("UPDATE tblUser SET wallet_balance = ? WHERE user_id = ?");
        $stmt->execute([$new_balance, $_SESSION['user_id']]);
        $_SESSION['wallet_balance'] = $new_balance;
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO tblOrder (user_id, total_amount, status, shipping_address, delivery_method) VALUES (?, ?, 'paid', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $grand_total, $shipping_address, $delivery_method]);
        $order_id = $pdo->lastInsertId();
        
        // Add order items
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, clothes_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['clothes_id'], $item['quantity'], $item['price']]);
        }
        
        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $success = "Order placed successfully! Order #" . $order_id;
    }
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>Checkout — Pastimes</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="index.php" class="logo">pastimes<span>.</span></a>
            <nav class="nav">
                <div class="icons">
                    <a href="cart.php" class="icon-link">← Back to cart</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div style="max-width: 600px; margin: 48px auto;">
        <h1>Checkout</h1>
        
        <?php if($success): ?>
            <div style="background: #eef4ee; padding: 24px; border-radius: 16px; margin: 24px 0;">
                <p style="color: var(--accent); font-weight: 500;">✓ <?= $success ?></p>
                <p style="margin-top: 16px;">We've emailed your order confirmation. Your items will ship within 2-3 business days.</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Continue shopping →</a>
            </div>
        <?php else: ?>
            <?php if($error): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 24px;"><?= $error ?></div>
            <?php endif; ?>
            
            <div style="background: var(--bg-secondary); padding: 24px; border-radius: 16px; margin: 24px 0;">
                <h3>Order summary</h3>
                <div style="display: flex; justify-content: space-between; margin: 12px 0;">
                    <span>Subtotal</span>
                    <span><?= formatPrice($total) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Shipping</span>
                    <span><?= $shipping == 0 ? 'Free' : formatPrice($shipping) ?></span>
                </div>
                <hr style="margin: 16px 0;">
                <div style="display: flex; justify-content: space-between; font-weight: 500;">
                    <span>Total</span>
                    <span><?= formatPrice($grand_total) ?></span>
                </div>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);">
                    <span>Wallet balance</span>
                    <span style="float: right;"><?= formatPrice($user['wallet_balance']) ?></span>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Delivery method</label>
                    <select name="delivery_method">
                        <option value="courier_guy">Courier Guy (R<?= $shipping ?>)</option>
                        <option value="postnet">POSTNET counter (R99)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Shipping address</label>
                    <textarea name="shipping_address" rows="3" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Pay <?= formatPrice($grand_total) ?> from wallet</button>
            </form>
        <?php endif; ?>
    </div>
</main>

</body>
</html>