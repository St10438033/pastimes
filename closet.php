<?php
session_start();

// FIXED: Use absolute path with __DIR__
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/includes/functions.php';


// FORCE LOGIN REQUIRED
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if columns exist, add if missing
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM tblUser LIKE 'is_seller_approved'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE tblUser ADD COLUMN is_seller_approved BOOLEAN DEFAULT FALSE");
    }
} catch (PDOException $e) {
    // Column might already exist
}

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM tblUser LIKE 'is_seller'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE tblUser ADD COLUMN is_seller BOOLEAN DEFAULT FALSE");
    }
} catch (PDOException $e) {
    // Column might already exist
}

// Check if user is approved directly from database
$stmt = $pdo->prepare("SELECT is_seller_approved FROM tblUser WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$is_approved = $user && $user['is_seller_approved'] == 1;

// If not approved, show error message
if (!$is_approved) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Access Denied — Pastimes</title>
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
                        <a href="profile.php" class="icon-link">Profile</a>
                        <a href="logout.php" class="icon-link">Logout</a>
                    </div>
                </nav>
            </div>
        </div>
    </header>
    <main class="container">
        <div style="max-width: 600px; margin: 80px auto; text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 24px;">🔒</div>
            <h1 style="margin-bottom: 16px;">Seller Access Required</h1>
            <p style="color: var(--text-muted); margin-bottom: 32px;">
                You need to be an approved seller to list items on Pastimes.
            </p>
            <div style="background: var(--bg-secondary); padding: 24px; border-radius: 16px; text-align: left; margin-bottom: 32px;">
                <p style="font-weight: 500; margin-bottom: 12px;">📋 How to become a seller:</p>
                <ol style="margin: 0 0 0 20px; color: var(--text-secondary); line-height: 2;">
                    <li>Go to your <a href="profile.php" style="color: var(--accent);">Profile</a></li>
                    <li>Click <strong>"Request to be a seller"</strong></li>
                    <li>Submit your request</li>
                    <li>Wait for admin approval</li>
                </ol>
            </div>
            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="profile.php" class="btn btn-primary">Go to Profile</a>
                <a href="index.php" class="btn btn-outline">Go Home</a>
            </div>
        </div>
    </main>
    </body>
    </html>
    <?php
    exit();
}


$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['list_product'])) {
    $title = $_POST['title'];
    $brand = $_POST['brand'];
    $price = $_POST['price'];
    $condition = $_POST['condition'];
    $era = $_POST['era'];
    $category = $_POST['category'];
    $size = $_POST['size'];
    $color = $_POST['color'];
    $how_to_wear = $_POST['how_to_wear'];
    $story_text = $_POST['story_text'];
    
    // Handle image uploads
    $uploaded_images = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file = [
                'name' => $_FILES['images']['name'][$key],
                'type' => $_FILES['images']['type'][$key],
                'tmp_name' => $tmp_name,
                'error' => $_FILES['images']['error'][$key],
                'size' => $_FILES['images']['size'][$key]
            ];
            $result = uploadImage($file);
            if ($result['success']) {
                $uploaded_images[] = $result['path'];
            }
        }
    }
    
    $image_path = !empty($uploaded_images) ? $uploaded_images[0] : 'https://placehold.co/600x600/e5e5e0/666666?text=Your+Item';
    
    // Store all images as JSON
    $images_json = json_encode($uploaded_images);
    
    $stmt = $pdo->prepare("INSERT INTO tblClothes (seller_id, title, brand, price, condition_status, era, category, size, color, how_to_wear, story_text, image_url, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $title, $brand, $price, $condition, $era, $category, $size, $color, $how_to_wear, $story_text, $image_path, $images_json])) {
        $success = "Item listed successfully!";
    } else {
        $error = "Failed to list item.";
    }
}

// Get user's listed items
$stmt = $pdo->prepare("SELECT * FROM tblClothes WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_items = $stmt->fetchAll();

// Check for deleted message
$deleted = isset($_GET['deleted']) ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sell — Pastimes</title>
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
                    <a href="profile.php" class="icon-link">Profile</a>
                    <a href="logout.php" class="icon-link">Logout</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div style="max-width: 600px; margin: 48px auto;">
        <h1>Sell your closet</h1>
        <p style="color: var(--text-muted); margin-bottom: 32px;">Turn pre-loved items into cash. Pastimes takes 10% commission.</p>
        
        <?php if($deleted): ?>
            <div style="background: #eef4ee; color: var(--accent); padding: 12px; border-radius: 12px; margin-bottom: 24px;">✓ Product deleted successfully.</div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div style="background: #eef4ee; color: var(--accent); padding: 12px; border-radius: 12px; margin-bottom: 24px;">✓ <?= $success ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 24px;"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="upload-area">
            <div style="font-size: 2rem; margin-bottom: 12px;">📸</div>
            <strong>Upload product images</strong>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">JPEG, PNG, WebP · Max 5MB each</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title / Brand style</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand">
            </div>
            <div class="form-group">
                <label>Price (ZAR)</label>
                <input type="number" name="price" required>
            </div>
            <div class="form-group">
                <label>Condition</label>
                <select name="condition">
                    <option>Like new</option>
                    <option>Great</option>
                    <option>Good</option>
                    <option>Worn with love</option>
                </select>
            </div>
            <div class="form-group">
                <label>Era (e.g., 1990s, Y2K)</label>
                <input type="text" name="era">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option>Streetwear</option>
                    <option>Vintage</option>
                    <option>Workwear</option>
                    <option>Contemporary</option>
                </select>
            </div>
            <div class="form-group">
                <label>Size</label>
                <input type="text" name="size" placeholder="S, M, L, XL, 32, etc.">
            </div>
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color">
            </div>
            <div class="form-group">
                <label>How to wear</label>
                <textarea name="how_to_wear" rows="2" placeholder="Style inspiration..."></textarea>
            </div>
            <div class="form-group">
                <label>Story / Background</label>
                <textarea name="story_text" rows="3" placeholder="Tell the story of this piece..."></textarea>
            </div>
            <div class="form-group">
                <label>Product Images</label>
                <input type="file" name="images[]" accept="image/*" multiple required>
                <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">Upload up to 5 images (JPEG, PNG, WebP). Max 5MB each.</p>
            </div>
            <button type="submit" name="list_product" class="btn btn-primary w-100">List for sale</button>
        </form>
        
        <?php if(!empty($my_items)): ?>
            <h3 style="margin-top: 48px;">Your listed items</h3>
            <?php foreach($my_items as $item): ?>
                <div style="display: flex; gap: 16px; align-items: center; padding: 16px; border-bottom: 1px solid var(--border);">
                    <img src="<?= htmlspecialchars($item['image_url']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                    <div style="flex: 1;">
                        <div style="font-weight: 500;"><?= htmlspecialchars($item['title']) ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($item['brand']) ?></div>
                    </div>
                    <div style="font-weight: 500;"><?= formatPrice($item['price']) ?></div>
                    <div style="display: flex; gap: 8px;">
                        <a href="edit_product.php?id=<?= $item['clothes_id'] ?>" class="btn btn-outline" style="padding: 4px 12px; font-size: 0.7rem; text-decoration: none;">Edit</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <div class="delivery-note">Free delivery over R1,200 · Courier Guy & POSTNET nationwide</div>
    </div>
</footer>

</body>
</html>