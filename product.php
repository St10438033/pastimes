<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$product_id = $_GET['id'] ?? 0;
$product = getClothesById($product_id);

if (!$product) {
    die("Product not found");
}

// Get seller info
$stmt = $pdo->prepare("SELECT full_name, is_seller_approved FROM tblUser WHERE user_id = ?");
$stmt->execute([$product['seller_id']]);
$seller = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    addToCart($product_id, $_POST['quantity'] ?? 1);
    header('Location: cart.php');
    exit();
}

// Handle offer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_offer'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    
    $offered_price = $_POST['offer_price'];
    $message = $_POST['offer_message'] ?? '';
    
    createOffer($product_id, $_SESSION['user_id'], $product['seller_id'], $offered_price, $message);
    $offer_success = "✅ Offer sent to seller!";
}

// Handle counter offer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['counter_offer'])) {
    counterOffer($_POST['offer_id'], $_POST['counter_price'], $_POST['counter_message']);
    header("Location: product.php?id=$product_id");
    exit();
}

// Handle accept offer
if (isset($_GET['accept_offer'])) {
    acceptOffer($_GET['accept_offer']);
    header("Location: product.php?id=$product_id");
    exit();
}

// Get offers for this product
$offers = [];
if (isset($_SESSION['user_id'])) {
    $offers = getProductOffers($product_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['title']) ?> — Pastimes</title>
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
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="cart.php" class="icon-link">Cart <?php 
                            $count = getCartCount();
                            if($count > 0) echo "<span class='cart-count'>$count</span>";
                        ?></a>
                    <?php else: ?>
                        <a href="login.php" class="icon-link">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div class="product-detail">
        <div class="product-gallery">
            <div class="main-image">
                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
            </div>
        </div>
        
        <div class="product-info">
            <h1><?= htmlspecialchars($product['title']) ?></h1>
            <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 8px;">
                <?= htmlspecialchars($product['brand']) ?> · <?= $product['era'] ?> · <?= $product['condition_status'] ?>
            </div>
            <div style="color: var(--text-muted); font-size: 0.7rem; margin-bottom: 16px;">
                Seller: <?= htmlspecialchars($seller['full_name'] ?? 'Unknown') ?>
                <?php if($seller['is_seller_approved']): ?>
                    <span style="color: var(--accent);">✓ Verified</span>
                <?php endif; ?>
            </div>
            <div class="price">
                <?= formatPrice($product['price']) ?>
                <span class="make-offer">Make an offer</span>
            </div>
            
            <form method="POST">
                <div class="size-selector">
                    <div style="font-size: 0.8rem; margin-bottom: 8px;">Quantity</div>
                    <input type="number" name="quantity" value="1" min="1" style="width: 80px; padding: 8px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <button type="submit" name="add_to_cart" class="btn btn-primary w-100" style="margin: 16px 0;">Add to cart</button>
            </form>
            
            <div class="style-guide">
                <strong>How to wear this</strong>
                <p><?= htmlspecialchars($product['how_to_wear'] ?? 'Layer with baggy denim and sneakers for a relaxed silhouette.') ?></p>
                <hr>
                <strong>Background</strong>
                <p><?= htmlspecialchars($product['story_text'] ?? 'Curated from South African vintage archives. Each piece tells a story.') ?></p>
            </div>
            
            <!-- OFFER SECTION -->
            <div style="margin-top: 24px;">
                <button onclick="document.getElementById('offerForm').style.display='block'" class="btn btn-outline" style="width:100%;">
                    💬 Make an Offer
                </button>
                
                <div id="offerForm" style="display: none; margin-top: 16px; padding: 20px; background: var(--bg-secondary); border-radius: 16px;">
                    <h4>Suggest your price</h4>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <p>Please <a href="login.php">login</a> to make an offer.</p>
                    <?php elseif($_SESSION['user_id'] == $product['seller_id']): ?>
                        <p style="color: var(--text-muted);">You can't make an offer on your own product.</p>
                    <?php else: ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Your offer (ZAR)</label>
                                <input type="number" name="offer_price" value="<?= $product['price'] * 0.8 ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Message to seller</label>
                                <textarea name="offer_message" rows="2" placeholder="Why this price? Any questions about the item..."></textarea>
                            </div>
                            <?php if(isset($offer_success)): ?>
                                <div style="background: #eef4ee; color: var(--accent); padding: 12px; border-radius: 12px; margin-bottom: 12px;"><?= $offer_success ?></div>
                            <?php endif; ?>
                            <button type="submit" name="make_offer" class="btn btn-primary" style="width:100%;">Send Offer</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- SHOW EXISTING OFFERS -->
            <?php if (!empty($offers) && isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $product['seller_id'] || $_SESSION['user_id'] == $offers[0]['buyer_id'] || $_SESSION['user_type'] == 'admin')): ?>
            <div style="margin-top: 32px; background: var(--bg-secondary); padding: 20px; border-radius: 16px;">
                <h4>💬 Offers & Negotiations</h4>
                <?php foreach($offers as $offer): ?>
                    <div style="background: white; padding: 16px; border-radius: 12px; margin: 12px 0; border-left: 4px solid <?= $offer['status'] == 'accepted' ? '#2d5a27' : ($offer['status'] == 'pending' ? '#c46b2b' : ($offer['status'] == 'countered' ? '#2b6bc4' : '#dc2626')) ?>;">
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <strong><?= htmlspecialchars($offer['buyer_name']) ?></strong>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                    offered R<?= number_format($offer['offered_price'], 2) ?>
                                </span>
                            </div>
                            <span style="font-size: 0.7rem; text-transform: uppercase; font-weight: 600; color: <?= $offer['status'] == 'accepted' ? 'var(--accent)' : ($offer['status'] == 'pending' ? '#c46b2b' : ($offer['status'] == 'countered' ? '#2b6bc4' : '#dc2626')) ?>;">
                                <?= $offer['status'] ?>
                            </span>
                        </div>
                        <?php if($offer['message']): ?>
                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">"<?= htmlspecialchars($offer['message']) ?>"</p>
                        <?php endif; ?>
                        
                        <?php if($offer['counter_offer_price']): ?>
                            <div style="background: var(--bg-secondary); padding: 12px; border-radius: 8px; margin-top: 8px;">
                                <span style="font-weight: 500;">Counter offer:</span>
                                R<?= number_format($offer['counter_offer_price'], 2) ?>
                                <?php if($offer['counter_offer_message']): ?>
                                    <p style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($offer['counter_offer_message']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action buttons for seller -->
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $product['seller_id'] && $offer['status'] == 'pending'): ?>
                            <div style="display: flex; gap: 8px; margin-top: 12px;">
                                <a href="product.php?id=<?= $product_id ?>&accept_offer=<?= $offer['offer_id'] ?>" class="btn btn-primary" style="padding: 4px 16px; font-size: 0.75rem;">Accept</a>
                                <button onclick="document.getElementById('counterForm_<?= $offer['offer_id'] ?>').style.display='block'" class="btn btn-outline" style="padding: 4px 16px; font-size: 0.75rem;">Counter</button>
                                <a href="product.php?id=<?= $product_id ?>&decline_offer=<?= $offer['offer_id'] ?>" class="btn btn-outline" style="padding: 4px 16px; font-size: 0.75rem; border-color: #dc2626; color: #dc2626;" onclick="return confirm('Decline this offer?')">Decline</a>
                            </div>
                            <div id="counterForm_<?= $offer['offer_id'] ?>" style="display: none; margin-top: 12px;">
                                <form method="POST" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <input type="hidden" name="offer_id" value="<?= $offer['offer_id'] ?>">
                                    <input type="number" name="counter_price" placeholder="Your price" value="<?= $offer['offered_price'] * 1.1 ?>" style="width: 120px; padding: 6px; border: 1px solid var(--border); border-radius: 8px;">
                                    <input type="text" name="counter_message" placeholder="Message" style="flex: 1; min-width: 150px; padding: 6px; border: 1px solid var(--border); border-radius: 8px;">
                                    <button type="submit" name="counter_offer" class="btn btn-primary" style="padding: 4px 16px; font-size: 0.75rem;">Send</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <div class="delivery-note">Free delivery over R1,200 · Courier Guy & POSTNET nationwide</div>
    </div>
</footer>

</body>
</html>