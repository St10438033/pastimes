<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>Shop — Pastimes</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="index.php" class="logo">pastimes<span>.</span></a>
            <nav class="nav">
                <div class="nav-links">
                    <a href="shop.php">Shop</a>
                    <a href="closet.php">Sell</a>
                    <a href="#">Drops</a>
                </div>
                <div class="icons">
                    <a href="shop.php" class="icon-link">Search</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="icon-link">Profile</a>
                        <a href="cart.php" class="icon-link">Cart <?php 
                            $count = getCartCount();
                            if($count > 0) echo "<span class='cart-count'>$count</span>";
                        ?></a>
                        <a href="logout.php" class="icon-link">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="icon-link">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div class="filters-bar">
        <button class="filter-chip">All</button>
        <button class="filter-chip">Vintage (90s-00s)</button>
        <button class="filter-chip">Streetwear</button>
        <button class="filter-chip">Workwear</button>
        <button class="filter-chip">Under R500</button>
    </div>
    
    <div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
        <span style="color: var(--text-muted); font-size: 0.8rem;">All pieces</span>
        <select style="padding: 6px 12px; border: 1px solid var(--border); border-radius: 8px;">
            <option>Newest first</option>
            <option>Price: low to high</option>
            <option>Price: high to low</option>
        </select>
    </div>
    
    <div class="product-grid">
        <?php
        $stmt = $pdo->query("SELECT * FROM tblClothes WHERE is_active = 1 ORDER BY created_at DESC");
        while($item = $stmt->fetch()):
        ?>
        <div class="product-card" onclick="window.location.href='product.php?id=<?= $item['clothes_id'] ?>'">
            <div class="product-image">
                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
            </div>
            <div class="product-title"><?= htmlspecialchars($item['title']) ?></div>
            <div class="product-brand"><?= htmlspecialchars($item['brand']) ?></div>
            <div class="product-price"><?= formatPrice($item['price']) ?></div>
        </div>
        <?php endwhile; ?>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <div class="delivery-note">Free delivery over R1,200 · Courier Guy & POSTNET nationwide</div>
    </div>
</footer>

</body>
</html>