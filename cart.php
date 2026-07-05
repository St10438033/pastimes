<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if (isset($_GET['remove'])) {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['remove'], $_SESSION['user_id']]);
    header('Location: cart.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['quantity'] as $cart_id => $qty) {
        if ($qty > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$qty, $cart_id, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
        }
    }
    header('Location: cart.php');
    exit();
}

$cart_items = getCartItems();
$total = getCartTotal();
$shipping = calculateShipping($total);
$grand_total = $total + $shipping;
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>Cart — Pastimes</title>
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
                    <a href="closet.php" class="icon-link">Sell</a>
                    <a href="profile.php" class="icon-link">Profile</a>
                    <a href="logout.php" class="icon-link">Logout</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <h1 style="margin: 48px 0 24px;">Your cart</h1>
    
    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 60px 0;">
            <p style="color: var(--text-muted); margin-bottom: 24px;">Your cart is empty</p>
            <a href="shop.php" class="btn btn-primary">Continue shopping →</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cart_items as $item): ?>
                    <tr>
                        <td>
                            <div class="cart-product">
                                <img src="<?= $item['image_url'] ?>" class="cart-product-image">
                                <div>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($item['title']) ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($item['brand']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <input type="number" name="quantity[<?= $item['cart_id'] ?>]" value="<?= $item['quantity'] ?>" min="0" class="quantity-input">
                        </td>
                        <td><?= formatPrice($item['price']) ?></td>
                        <td><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                        <td><a href="cart.php?remove=<?= $item['cart_id'] ?>" class="remove-link">Remove</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="display: flex; justify-content: flex-end; gap: 16px; margin: 24px 0;">
                <button type="submit" name="update" class="btn btn-outline">Update cart</button>
                <a href="checkout.php" class="btn btn-primary">Proceed to checkout</a>
            </div>
        </form>
        
        <div style="background: var(--bg-secondary); padding: 24px; border-radius: 16px; max-width: 320px; margin-left: auto;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span>Subtotal</span>
                <span><?= formatPrice($total) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span>Shipping</span>
                <span><?= $shipping == 0 ? 'Free' : formatPrice($shipping) ?></span>
            </div>
            <hr style="margin: 16px 0;">
            <div style="display: flex; justify-content: space-between; font-weight: 500;">
                <span>Total</span>
                <span><?= formatPrice($grand_total) ?></span>
            </div>
        </div>
    <?php endif; ?>
</main>

</body>
</html>