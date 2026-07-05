<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/upload.php';

requireLogin();

$clothes_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Verify ownership
if (!userOwnsProduct($user_id, $clothes_id)) {
    header('Location: profile.php?error=not_owner');
    exit();
}

// Get product data
$stmt = $pdo->prepare("SELECT * FROM tblClothes WHERE clothes_id = ?");
$stmt->execute([$clothes_id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found");
}

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update product details
    if (isset($_POST['update_product'])) {
        $data = [
            'title' => $_POST['title'],
            'brand' => $_POST['brand'],
            'price' => $_POST['price'],
            'condition_status' => $_POST['condition'],
            'era' => $_POST['era'],
            'category' => $_POST['category'],
            'size' => $_POST['size'],
            'color' => $_POST['color'],
            'how_to_wear' => $_POST['how_to_wear'],
            'story_text' => $_POST['story_text']
        ];
        
        if (updateProduct($clothes_id, $data)) {
            $success = "Product updated successfully!";
            // Refresh product data
            $stmt = $pdo->prepare("SELECT * FROM tblClothes WHERE clothes_id = ?");
            $stmt->execute([$clothes_id]);
            $product = $stmt->fetch();
        } else {
            $error = "Failed to update product.";
        }
    }
    
    // Handle image removal
    if (isset($_POST['remove_image'])) {
        $remove_path = $_POST['remove_image'];
        $images = json_decode($product['images'] ?? '[]', true);
        if (!is_array($images)) {
            $images = [];
        }
        
        if (($key = array_search($remove_path, $images)) !== false) {
            unset($images[$key]);
            $images = array_values($images);
            deleteImage($remove_path);
            
            $stmt = $pdo->prepare("UPDATE tblClothes SET images = ? WHERE clothes_id = ?");
            $stmt->execute([json_encode($images), $clothes_id]);
            
            // Update primary image
            if (!empty($images)) {
                $stmt = $pdo->prepare("UPDATE tblClothes SET image_url = ? WHERE clothes_id = ?");
                $stmt->execute([$images[0], $clothes_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE tblClothes SET image_url = NULL WHERE clothes_id = ?");
                $stmt->execute([$clothes_id]);
            }
            
            $success = "Image removed!";
            // Refresh
            $stmt = $pdo->prepare("SELECT * FROM tblClothes WHERE clothes_id = ?");
            $stmt->execute([$clothes_id]);
            $product = $stmt->fetch();
        }
    }
    
    // Handle image upload
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $uploaded = [];
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
                $uploaded[] = $result['path'];
            }
        }
        
        if (!empty($uploaded)) {
            // Get existing images
            $images = json_decode($product['images'] ?? '[]', true);
            if (!is_array($images)) {
                $images = [];
            }
            $images = array_merge($images, $uploaded);
            
            $stmt = $pdo->prepare("UPDATE tblClothes SET images = ? WHERE clothes_id = ?");
            $stmt->execute([json_encode($images), $clothes_id]);
            
            // Update primary image
            $stmt = $pdo->prepare("UPDATE tblClothes SET image_url = ? WHERE clothes_id = ?");
            $stmt->execute([$images[0], $clothes_id]);
            
            $success = "Images uploaded!";
            // Refresh
            $stmt = $pdo->prepare("SELECT * FROM tblClothes WHERE clothes_id = ?");
            $stmt->execute([$clothes_id]);
            $product = $stmt->fetch();
        }
    }
    
    // Handle delete product
    if (isset($_POST['delete_product'])) {
        $result = deleteProduct($clothes_id, $user_id);
        if ($result['success']) {
            header('Location: closet.php?deleted=1');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// Get images
$images = json_decode($product['images'] ?? '[]', true);
if (!is_array($images)) {
    $images = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product — Pastimes</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .image-gallery {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin: 16px 0;
        }
        .image-item {
            width: 120px;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 8px;
            text-align: center;
            position: relative;
        }
        .image-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .image-item .remove-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 14px;
            line-height: 24px;
            text-align: center;
        }
        .image-item .primary-badge {
            font-size: 0.6rem;
            color: var(--accent);
            margin-top: 4px;
        }
        .btn-danger {
            background: transparent;
            border: 1px solid #dc2626;
            color: #dc2626;
        }
        .btn-danger:hover {
            background: #dc2626;
            color: white;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="index.php" class="logo">pastimes<span>.</span></a>
            <nav class="nav">
                <div class="icons">
                    <a href="closet.php" class="icon-link">← Back to Closet</a>
                    <a href="logout.php" class="icon-link">Logout</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div style="max-width: 700px; margin: 48px auto;">
        <h1>Edit Product</h1>
        <p style="color: var(--text-muted); margin-bottom: 24px;">Update your listing details and images.</p>
        
        <?php if($success): ?>
            <div style="background: #eef4ee; color: var(--accent); padding: 12px; border-radius: 12px; margin-bottom: 16px;">✓ <?= $success ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 16px;"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- PRODUCT IMAGES -->
        <div style="background: var(--bg-secondary); padding: 24px; border-radius: 16px; margin-bottom: 24px;">
            <h3>Product Images</h3>
            
            <?php if(!empty($images)): ?>
                <div class="image-gallery">
                    <?php foreach($images as $index => $img): ?>
                        <div class="image-item">
                            <img src="<?= htmlspecialchars($img) ?>" alt="Product image">
                            <?php if($index === 0): ?>
                                <div class="primary-badge">★ Primary</div>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="remove_image" value="<?= htmlspecialchars($img) ?>">
                                <button type="submit" class="remove-btn" onclick="return confirm('Remove this image?')">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No images uploaded yet.</p>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" style="margin-top: 16px;">
                <div class="form-group">
                    <label>Add more images</label>
                    <input type="file" name="images[]" accept="image/*" multiple>
                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">JPEG, PNG, WebP · Max 5MB each</p>
                </div>
                <button type="submit" class="btn btn-primary">Upload Images</button>
            </form>
        </div>
        
        <!-- PRODUCT DETAILS -->
        <form method="POST">
            <div class="form-group">
                <label>Title / Brand style</label>
                <input type="text" name="title" value="<?= htmlspecialchars($product['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand" value="<?= htmlspecialchars($product['brand']) ?>">
            </div>
            <div class="form-group">
                <label>Price (ZAR)</label>
                <input type="number" name="price" value="<?= $product['price'] ?>" required>
            </div>
            <div class="form-group">
                <label>Condition</label>
                <select name="condition">
                    <option <?= $product['condition_status'] == 'Like new' ? 'selected' : '' ?>>Like new</option>
                    <option <?= $product['condition_status'] == 'Great' ? 'selected' : '' ?>>Great</option>
                    <option <?= $product['condition_status'] == 'Good' ? 'selected' : '' ?>>Good</option>
                    <option <?= $product['condition_status'] == 'Worn with love' ? 'selected' : '' ?>>Worn with love</option>
                </select>
            </div>
            <div class="form-group">
                <label>Era (e.g., 1990s, Y2K)</label>
                <input type="text" name="era" value="<?= htmlspecialchars($product['era']) ?>">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option <?= $product['category'] == 'Streetwear' ? 'selected' : '' ?>>Streetwear</option>
                    <option <?= $product['category'] == 'Vintage' ? 'selected' : '' ?>>Vintage</option>
                    <option <?= $product['category'] == 'Workwear' ? 'selected' : '' ?>>Workwear</option>
                    <option <?= $product['category'] == 'Contemporary' ? 'selected' : '' ?>>Contemporary</option>
                </select>
            </div>
            <div class="form-group">
                <label>Size</label>
                <input type="text" name="size" value="<?= htmlspecialchars($product['size']) ?>" placeholder="S, M, L, XL, 32, etc.">
            </div>
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" value="<?= htmlspecialchars($product['color']) ?>">
            </div>
            <div class="form-group">
                <label>How to wear</label>
                <textarea name="how_to_wear" rows="2"><?= htmlspecialchars($product['how_to_wear']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Story / Background</label>
                <textarea name="story_text" rows="3"><?= htmlspecialchars($product['story_text']) ?></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px;">
                <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                <button type="submit" name="delete_product" class="btn btn-danger" onclick="return confirm('⚠️ Delete this product permanently? This cannot be undone.')">Delete Product</button>
            </div>
        </form>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <div class="delivery-note">Free delivery over R1,200 · Courier Guy & POSTNET nationwide</div>
    </div>
</footer>

</body>
</html>