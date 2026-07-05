<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Require admin login
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Initialize variables (FIX FOR THE NOTICE)
$success = '';
$error = '';

// Get counts for dashboard
$total_users = $pdo->query("SELECT COUNT(*) FROM tblUser")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM tblClothes")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM tblOrder")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM tblOrder WHERE status = 'paid'")->fetchColumn();
$pending_requests = $pdo->query("SELECT COUNT(*) FROM seller_requests WHERE status = 'pending'")->fetchColumn();

// Handle approve/deny seller
if (isset($_POST['approve_seller'])) {
    $request_id = $_POST['request_id'];
    $user_id = $_POST['user_id'];
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE seller_requests SET status = 'approved', reviewed_date = NOW() WHERE request_id = ?");
        $stmt->execute([$request_id]);
        
        $stmt = $pdo->prepare("UPDATE tblUser SET is_seller_approved = 1, is_seller = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        $success = "✅ Seller approved!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

if (isset($_POST['deny_seller'])) {
    $request_id = $_POST['request_id'];
    $stmt = $pdo->prepare("UPDATE seller_requests SET status = 'denied', reviewed_date = NOW() WHERE request_id = ?");
    $stmt->execute([$request_id]);
    $success = "❌ Seller denied.";
}

// Handle product delete
if (isset($_GET['delete_product'])) {
    $stmt = $pdo->prepare("DELETE FROM tblClothes WHERE clothes_id = ?");
    $stmt->execute([$_GET['delete_product']]);
    header('Location: admin_dashboard.php?tab=products');
    exit();
}

// Handle order status update
if (isset($_POST['update_order'])) {
    $stmt = $pdo->prepare("UPDATE tblOrder SET status = ? WHERE order_id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    header('Location: admin_dashboard.php?tab=orders');
    exit();
}

$active_tab = $_GET['tab'] ?? 'overview';

// Get recent orders
$recent_orders = $pdo->query("SELECT * FROM tblOrder ORDER BY order_date DESC LIMIT 5")->fetchAll();
$recent_users = $pdo->query("SELECT * FROM tblUser ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard — Pastimes</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-sidebar {
            width: 260px;
            background: var(--bg-secondary);
            min-height: calc(100vh - 80px);
            position: fixed;
            left: 0;
            top: 80px;
            border-right: 1px solid var(--border);
            padding: 32px 0;
        }
        
        .admin-sidebar a {
            display: block;
            padding: 12px 24px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .admin-sidebar a:hover {
            background: var(--border);
        }
        
        .admin-sidebar a.active {
            background: var(--accent);
            color: white;
        }
        
        .admin-main {
            margin-left: 260px;
            padding: 32px 48px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 600;
            color: var(--accent);
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 8px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .admin-table th {
            background: var(--bg-secondary);
            padding: 12px 16px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .admin-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .status-paid { background: #eef4ee; color: var(--accent); }
        .status-pending { background: #fef2e8; color: #c46b2b; }
        .status-shipped { background: #e8f0fe; color: #2b6bc4; }
        .status-delivered { background: #eef4ee; color: var(--accent); }
        
        .badge {
            background: #dc2626;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 8px;
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
                    <a href="logout.php" class="icon-link">Logout</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<div class="admin-sidebar">
    <a href="admin_dashboard.php?tab=overview" class="<?= $active_tab == 'overview' ? 'active' : '' ?>">Overview</a>
    <a href="admin_dashboard.php?tab=users" class="<?= $active_tab == 'users' ? 'active' : '' ?>">Users</a>
    <a href="admin_dashboard.php?tab=products" class="<?= $active_tab == 'products' ? 'active' : '' ?>">Products</a>
    <a href="admin_dashboard.php?tab=orders" class="<?= $active_tab == 'orders' ? 'active' : '' ?>">Orders</a>
    <a href="admin_dashboard.php?tab=seller_requests" class="<?= $active_tab == 'seller_requests' ? 'active' : '' ?>">
        Seller Requests
        <?php if($pending_requests > 0): ?>
            <span class="badge"><?= $pending_requests ?></span>
        <?php endif; ?>
    </a>
    <a href="admin_add_product.php?tab=add_product" class="<?= $active_tab == 'add_product' ? 'active' : '' ?>">Add Product</a>
</div>

<main class="admin-main">
    <?php if($success): ?>
        <div style="background: #eef4ee; color: var(--accent); padding: 12px; border-radius: 12px; margin-bottom: 24px;"><?= $success ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 24px;"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($active_tab == 'overview'): ?>
        <h1>Dashboard Overview</h1>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin: 32px 0;">
            <div class="stat-card">
                <div class="stat-number"><?= $total_users ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_products ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_orders ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">R <?= number_format($total_revenue ?? 0, 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        
        <h3>Recent Orders</h3>
        <table class="admin-table" style="margin-top: 16px;">
            <thead>
                <tr><th>Order ID</th><th>User ID</th><th>Total</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach($recent_orders as $order): ?>
                <tr>
                    <td>#<?= $order['order_id'] ?></td>
                    <td><?= $order['user_id'] ?></td>
                    <td>R <?= number_format($order['total_amount'], 2) ?></td>
                    <td><span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                    <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3 style="margin-top: 32px;">Recent Users</h3>
        <table class="admin-table" style="margin-top: 16px;">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Wallet</th><th>Joined</th></tr></thead>
            <tbody>
                <?php foreach($recent_users as $user): ?>
                <tr>
                    <td><?= $user['user_id'] ?></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>R <?= number_format($user['wallet_balance'], 2) ?></td>
                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
    <?php elseif ($active_tab == 'users'): ?>
        <h1>Users</h1>
        <table class="admin-table" style="margin-top: 24px; width: 100%;">
            <thead>
                <tr><th>ID</th><th>Full Name</th><th>Email</th><th>Phone</th><th>City</th><th>Wallet</th><th>Seller</th><th>Approved</th><th>Joined</th></tr>
            </thead>
            <tbody>
                <?php
                $users = $pdo->query("SELECT * FROM tblUser ORDER BY created_at DESC")->fetchAll();
                foreach($users as $user):
                ?>
                <tr>
                    <td><?= $user['user_id'] ?></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($user['city'] ?? '-') ?></td>
                    <td>R <?= number_format($user['wallet_balance'], 2) ?></td>
                    <td><?= $user['is_seller'] ? '✓' : '✗' ?></td>
                    <td><?= $user['is_seller_approved'] ? '✅' : '⏳' ?></td>
                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
    <?php elseif ($active_tab == 'products'): ?>
        <h1>Products</h1>
        <table class="admin-table" style="margin-top: 24px; width: 100%;">
            <thead>
                <tr><th>ID</th><th>Image</th><th>Title</th><th>Brand</th><th>Price</th><th>Seller ID</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php
                $products = $pdo->query("SELECT * FROM tblClothes ORDER BY created_at DESC")->fetchAll();
                foreach($products as $product):
                ?>
                <tr>
                    <td><?= $product['clothes_id'] ?></td>
                    <td><img src="<?= htmlspecialchars($product['image_url']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;"></td>
                    <td><?= htmlspecialchars($product['title']) ?></td>
                    <td><?= htmlspecialchars($product['brand']) ?></td>
                    <td>R <?= number_format($product['price'], 2) ?></td>
                    <td><?= $product['seller_id'] ?></td>
                    <td><?= $product['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <a href="admin_dashboard.php?delete_product=<?= $product['clothes_id'] ?>&tab=products" onclick="return confirm('Delete this product?')" style="color: #dc2626; font-size: 0.75rem;">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
    <?php elseif ($active_tab == 'orders'): ?>
        <h1>Orders</h1>
        <table class="admin-table" style="margin-top: 24px; width: 100%;">
            <thead>
                <tr><th>Order ID</th><th>User ID</th><th>Total</th><th>Status</th><th>Delivery</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php
                $orders = $pdo->query("SELECT * FROM tblOrder ORDER BY order_date DESC")->fetchAll();
                foreach($orders as $order):
                ?>
                <tr>
                    <td>#<?= $order['order_id'] ?></td>
                    <td><?= $order['user_id'] ?></td>
                    <td>R <?= number_format($order['total_amount'], 2) ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="paid" <?= $order['status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            </select>
                            <input type="submit" name="update_order" value="Update" style="display: none;">
                        </form>
                    </td>
                    <td><?= htmlspecialchars($order['delivery_method'] ?? '-') ?></td>
                    <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                    <td>
                        <a href="order_detail.php?id=<?= $order['order_id'] ?>" style="color: var(--accent); font-size: 0.75rem;">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
    <?php elseif ($active_tab == 'seller_requests'): ?>
        <h1>Seller Requests</h1>
        <p style="color: var(--text-muted);">Review and approve/deny seller applications.</p>
        
        <?php
        $requests = $pdo->query("
            SELECT r.*, u.is_seller_approved, u.email, u.full_name 
            FROM seller_requests r 
            JOIN tblUser u ON r.user_id = u.user_id 
            WHERE r.status = 'pending' 
            ORDER BY r.request_date ASC
        ")->fetchAll();
        ?>
        
        <?php if(empty($requests)): ?>
            <p style="color: var(--text-muted); margin-top: 24px;">No pending seller requests.</p>
        <?php else: ?>
            <?php foreach($requests as $req): ?>
                <div style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin: 16px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h3><?= htmlspecialchars($req['full_name']) ?></h3>
                            <p style="color: var(--text-muted); font-size: 0.8rem;">
                                <?= htmlspecialchars($req['email']) ?> 
                                <?php if($req['phone']): ?>· <?= htmlspecialchars($req['phone']) ?><?php endif; ?>
                            </p>
                            <?php if($req['motivation']): ?>
                                <p style="margin-top: 8px; font-size: 0.875rem;">"<?= htmlspecialchars($req['motivation']) ?>"</p>
                            <?php endif; ?>
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">
                                Requested: <?= date('d M Y, H:i', strtotime($req['request_date'])) ?>
                            </p>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>">
                                <input type="hidden" name="user_id" value="<?= $req['user_id'] ?>">
                                <button type="submit" name="approve_seller" class="btn btn-primary" style="padding: 8px 20px;">Approve</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>">
                                <button type="submit" name="deny_seller" class="btn btn-outline" style="padding: 8px 20px; border-color: #dc2626; color: #dc2626;">Deny</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
    <?php elseif ($active_tab == 'add_product'): ?>
        <h1>Add New Product</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
            $stmt = $pdo->prepare("INSERT INTO tblClothes (seller_id, title, brand, price, condition_status, era, category, size, color, how_to_wear, story_text, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([1, $_POST['title'], $_POST['brand'], $_POST['price'], $_POST['condition_status'], $_POST['era'], $_POST['category'], $_POST['size'], $_POST['color'], $_POST['how_to_wear'], $_POST['story_text'], $_POST['image_url']]);
            echo '<div style="background: #eef4ee; color: var(--accent); padding: 12px; border-radius: 12px; margin-bottom: 24px;">✓ Product added successfully!</div>';
        }
        ?>
        
        <form method="POST" style="max-width: 600px; margin-top: 24px;">
            <div class="form-group">
                <label>Title</label>
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
                <select name="condition_status">
                    <option>Like new</option><option>Great</option><option>Good</option><option>Worn</option>
                </select>
            </div>
            <div class="form-group">
                <label>Era</label>
                <input type="text" name="era" placeholder="1990s, Y2K, 2022">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option>Streetwear</option><option>Vintage</option><option>Workwear</option><option>Contemporary</option>
                </select>
            </div>
            <div class="form-group">
                <label>Size</label>
                <input type="text" name="size">
            </div>
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color">
            </div>
            <div class="form-group">
                <label>How to wear</label>
                <textarea name="how_to_wear" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Story / Background</label>
                <textarea name="story_text" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Image URL</label>
                <input type="text" name="image_url" placeholder="https://...">
            </div>
            <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
        </form>
    <?php endif; ?>
</main>

</body>
</html>