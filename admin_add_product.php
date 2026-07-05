<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';

// Require admin login
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

$success = '';
$error = '';

// Get all sellers for dropdown
$sellers = $pdo->query("SELECT user_id, full_name, email FROM tblUser WHERE is_seller_approved = 1 ORDER BY full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $seller_id = $_POST['seller_id'];
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
    $image_path = 'https://placehold.co/600x600/e5e5e0/666666?text=No+Image';
    
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
            } else {
                $error = "Failed to upload: " . $result['message'];
            }
        }
        
        if (!empty($uploaded_images)) {
            $image_path = $uploaded_images[0];
        }
    } else {
        $error = "Please upload at least one image.";
    }
    
    // If no error, insert into database
    if (empty($error)) {
        $images_json = json_encode($uploaded_images);
        
        $stmt = $pdo->prepare("
            INSERT INTO tblClothes (
                seller_id, title, brand, price, condition_status, era, 
                category, size, color, how_to_wear, story_text, image_url, images, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        if ($stmt->execute([
            $seller_id, $title, $brand, $price, $condition, $era,
            $category, $size, $color, $how_to_wear, $story_text, $image_path, $images_json
        ])) {
            $success = "✅ Product added successfully!";
            
            // Clear form fields after success
            $_POST = [];
        } else {
            $error = "❌ Failed to add product to database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product — Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .preview-container {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin: 12px 0;
        }
        .preview-item {
            width: 100px;
            height: 100px;
            border: 2px dashed var(--border);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-secondary);
            position: relative;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .remove-preview {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
            line-height: 20px;
            text-align: center;
        }
        .image-upload-area {
            border: 2px dashed var(--border);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--bg-secondary);
        }
        .image-upload-area:hover {
            border-color: var(--accent);
            background: #f0f5f0;
        }
        .image-upload-area.dragover {
            border-color: var(--accent);
            background: #e8f0e8;
        }
        .image-upload-area input[type="file"] {
            display: none;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
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
                    <span style="font-size: 0.875rem;">Admin: <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                    <a href="admin_dashboard.php" class="icon-link">← Dashboard</a>
                    <a href="logout.php" class="icon-link">Logout</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div style="max-width: 700px; margin: 48px auto;">
        <h1>Add New Product</h1>
        <p style="color: var(--text-muted); margin-bottom: 24px;">Upload images and fill in product details.</p>
        
        <?php if($success): ?>
            <div style="background: #eef4ee; color: var(--accent); padding: 16px; border-radius: 12px; margin-bottom: 24px; border-left: 4px solid var(--accent);">
                <?= $success ?>
                <br><br>
                <a href="admin_dashboard.php?tab=products" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.8rem;">View All Products</a>
                <a href="admin_add_product.php" class="btn btn-outline" style="padding: 8px 20px; font-size: 0.8rem;">Add Another</a>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border-left: 4px solid #dc2626;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="productForm">
            <!-- IMAGE UPLOAD -->
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 500; margin-bottom: 8px;">Product Images</label>
                <div class="image-upload-area" id="dropArea">
                    <div style="font-size: 3rem; margin-bottom: 12px;">📸</div>
                    <strong>Drop images here or click to browse</strong>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">
                        JPEG, PNG, WebP · Max 5MB each · Upload up to 5 images
                    </p>
                    <input type="file" name="images[]" accept="image/*" multiple id="imageInput" required>
                </div>
                <div class="preview-container" id="previewContainer"></div>
            </div>
            
            <!-- PRODUCT DETAILS -->
            <div class="form-row">
                <div class="form-group">
                    <label>Seller <span style="color: #dc2626;">*</span></label>
                    <select name="seller_id" required>
                        <option value="">Select a seller...</option>
                        <?php foreach($sellers as $seller): ?>
                            <option value="<?= $seller['user_id'] ?>">
                                <?= htmlspecialchars($seller['full_name']) ?> (<?= htmlspecialchars($seller['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="title" required placeholder="e.g., Vintage Nike Crewneck">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" placeholder="Nike, Carhartt, Stussy...">
                </div>
                <div class="form-group">
                    <label>Price (ZAR) <span style="color: #dc2626;">*</span></label>
                    <input type="number" name="price" required step="0.01" placeholder="650">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Condition</label>
                    <select name="condition">
                        <option>Like new</option>
                        <option selected>Great</option>
                        <option>Good</option>
                        <option>Worn with love</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Era</label>
                    <input type="text" name="era" placeholder="1990s, Y2K, 2022...">
                </div>
            </div>
            
            <div class="form-row">
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
                    <input type="text" name="size" placeholder="S, M, L, XL, 32, 42...">
                </div>
            </div>
            
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" placeholder="Navy, Black, Brown...">
            </div>
            
            <div class="form-group">
                <label>How to wear</label>
                <textarea name="how_to_wear" rows="2" placeholder="Style inspiration..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Story / Background</label>
                <textarea name="story_text" rows="3" placeholder="Tell the story of this piece..."></textarea>
            </div>
            
            <button type="submit" name="add_product" class="btn btn-primary w-100" style="margin-top: 8px;">
                Add Product
            </button>
        </form>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <div class="delivery-note">Pastimes Admin — South Africa's curated marketplace</div>
    </div>
</footer>

<!-- ============================================
JAVASCRIPT: IMAGE PREVIEW & DRAG & DROP
============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.getElementById('dropArea');
    const fileInput = document.getElementById('imageInput');
    const previewContainer = document.getElementById('previewContainer');
    let selectedFiles = [];
    const MAX_FILES = 5;
    const MAX_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    
    // Click to upload
    dropArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // File selection
    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });
    
    // Drag and drop
    dropArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    dropArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    dropArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });
    
    function handleFiles(files) {
        const newFiles = Array.from(files);
        
        // Check total files limit
        if (selectedFiles.length + newFiles.length > MAX_FILES) {
            alert(`You can only upload up to ${MAX_FILES} images. You already have ${selectedFiles.length} selected.`);
            return;
        }
        
        // Validate each file
        for (const file of newFiles) {
            // Check file type
            if (!ALLOWED_TYPES.includes(file.type)) {
                alert(`"${file.name}" is not a valid image type. Use JPEG, PNG, or WebP.`);
                return;
            }
            
            // Check file size
            if (file.size > MAX_SIZE) {
                alert(`"${file.name}" is too large. Max 5MB.`);
                return;
            }
            
            selectedFiles.push(file);
        }
        
        // Update the file input
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        
        renderPreviews();
    }
    
    function renderPreviews() {
        previewContainer.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}">
                    <button type="button" class="remove-preview" data-index="${index}">×</button>
                `;
                previewContainer.appendChild(div);
                
                // Remove button
                div.querySelector('.remove-preview').addEventListener('click', function() {
                    removeFile(parseInt(this.dataset.index));
                });
            };
            reader.readAsDataURL(file);
        });
    }
    
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        
        // Update file input
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        
        renderPreviews();
    }
    
    // Optional: Reset form on success
    <?php if($success): ?>
        // Clear previews after successful submission
        selectedFiles = [];
        renderPreviews();
        fileInput.value = '';
    <?php endif; ?>
});
</script>

</body>
</html>