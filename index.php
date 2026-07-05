<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pastimes — Vintage & Streetwear, South Africa</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* ============================================
           HERO SLIDESHOW STYLES
           ============================================ */
        .hero {
            position: relative;
            overflow: hidden;
            background: var(--bg-secondary);
            padding: 80px 0;
            text-align: center;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-slideshow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
            filter: brightness(0.4) saturate(0.6);
        }
        
        .hero-slide.active {
            opacity: 1;
        }
        
        .hero-slide-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
            max-width: 700px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 500;
            margin-bottom: 20px;
            color: white;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }
        
        .hero-content p {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.85);
            max-width: 560px;
            margin: 0 auto 32px;
            text-shadow: 0 1px 10px rgba(0, 0, 0, 0.3);
        }
        
        .hero-content .btn-primary {
            background: white;
            color: var(--text-primary);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .hero-content .btn-primary:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
        }
        
        /* Slideshow indicators */
        .hero-indicators {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            display: flex;
            gap: 12px;
        }
        
        .hero-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            padding: 0;
        }
        
        .hero-indicator.active {
            background: white;
            transform: scale(1.3);
        }
        
        .hero-indicator:hover {
            background: rgba(255, 255, 255, 0.8);
        }
        
        /* Product grid remains the same */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 40px;
            margin: 56px 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                min-height: 400px;
                padding: 60px 0;
            }
            .hero-content h1 {
                font-size: 2.2rem;
            }
            .hero-content p {
                font-size: 1rem;
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
                <div class="nav-links">
                    <a href="shop.php">Shop</a>
                    <a href="closet.php">Sell</a>
                    <a href="#">Drops</a>
                    <a href="#">Journal</a>
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
                        <a href="register.php" class="icon-link">Register</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </div>
</header>

<main>
    <!-- ============================================
    HERO WITH SLIDESHOW
    ============================================ -->
    <section class="hero" id="heroSection">
        <!-- Slideshow Images -->
        <div class="hero-slideshow" id="heroSlideshow">
            <!-- Slides will be injected by PHP/JS -->
        </div>
        <div class="hero-slide-overlay"></div>
        
        <!-- Content Overlay -->
        <div class="hero-content">
            <h1>wear your story.</h1>
            <p>Curated vintage, streetwear, and contemporary pieces — built for those who value authenticity. Based in South Africa, shipping nationwide.</p>
            <a href="shop.php" class="btn btn-primary">Explore collection →</a>
        </div>
        
        <!-- Indicators -->
        <div class="hero-indicators" id="heroIndicators"></div>
    </section>

    <!-- ============================================
    DROP CARD
    ============================================ -->
    <div class="container">
        <div class="drop-card">
            <span class="stock-alert">Limited release — 47 pieces remaining</span>
            <h2>Archive Drop #003</h2>
            <p>90s vintage denim jackets · curated from Cape Town archives</p>
            <div class="countdown">
                <div class="countdown-item"><div class="countdown-number" id="hours">14</div><div class="countdown-label">Hours</div></div>
                <div class="countdown-item"><div class="countdown-number" id="minutes">23</div><div class="countdown-label">Minutes</div></div>
                <div class="countdown-item"><div class="countdown-number" id="seconds">42</div><div class="countdown-label">Seconds</div></div>
            </div>
            <button class="btn btn-primary" onclick="alert('Notify me feature coming soon')">Notify me</button>
        </div>
    </div>

    <!-- ============================================
    PRODUCT GRID
    ============================================ -->
    <div class="container">
        <h2>New arrivals</h2>
        <div class="product-grid">
            <?php
            $stmt = $pdo->query("SELECT * FROM tblClothes WHERE is_active = 1 ORDER BY created_at DESC LIMIT 4");
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
    </div>

    <!-- ============================================
    DELIVERY NOTE
    ============================================ -->
    <div class="container">
        <div class="delivery-note">
            Proudly South African — Free delivery over R1,200 · Courier Guy & POSTNET
        </div>
    </div>
</main>

<!-- ============================================
FOOTER
============================================ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>pastimes</h4>
                <a href="#">About</a>
                <a href="#">Stories</a>
                <a href="#">Sustainability</a>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <a href="shop.php">All products</a>
                <a href="#">Vintage</a>
                <a href="#">Streetwear</a>
                <a href="#">Drops</a>
            </div>
            <div class="footer-col">
                <h4>Sell</h4>
                <a href="closet.php">Sell your items</a>
                <a href="#">How it works</a>
                <a href="#">Commission</a>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <a href="#">Shipping</a>
                <a href="#">Returns</a>
                <a href="#">Contact</a>
            </div>
        </div>
        <div class="text-center" style="font-size: 0.7rem; color: var(--text-muted);">
            © 2025 Pastimes — South Africa
        </div>
    </div>
</footer>

<!-- ============================================
JAVASCRIPT: SLIDESHOW & COUNTDOWN
============================================ -->
<script>
// ============================================
// HERO SLIDESHOW
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Get product images from database via PHP
    const productImages = [
        <?php
        $stmt = $pdo->query("SELECT image_url FROM tblClothes WHERE is_active = 1 AND image_url IS NOT NULL LIMIT 10");
        $images = $stmt->fetchAll();
        foreach($images as $img):
            echo "'" . addslashes($img['image_url']) . "',";
        endforeach;
        
        // Fallback images if no products exist
        if (empty($images)): ?>
            'https://placehold.co/1920x1080/e5e5e0/666666?text=Pastimes',
            'https://placehold.co/1920x1080/e5e5e0/666666?text=Vintage+Streetwear',
            'https://placehold.co/1920x1080/e5e5e0/666666?text=South+African+Style'
        <?php endif; ?>
    ];
    
    // Remove duplicates and empty values
    const uniqueImages = [...new Set(productImages.filter(img => img && img.trim() !== ''))];
    
    if (uniqueImages.length === 0) {
        uniqueImages.push('https://placehold.co/1920x1080/e5e5e0/666666?text=Pastimes');
    }
    
    const slideshow = document.getElementById('heroSlideshow');
    const indicators = document.getElementById('heroIndicators');
    let currentSlide = 0;
    let slideInterval;
    
    // Create slides
    uniqueImages.forEach((img, index) => {
        const slide = document.createElement('div');
        slide.className = 'hero-slide' + (index === 0 ? ' active' : '');
        slide.style.backgroundImage = `url('${img}')`;
        slideshow.appendChild(slide);
        
        // Create indicator
        const indicator = document.createElement('button');
        indicator.className = 'hero-indicator' + (index === 0 ? ' active' : '');
        indicator.setAttribute('data-index', index);
        indicator.addEventListener('click', function() {
            goToSlide(parseInt(this.getAttribute('data-index')));
        });
        indicators.appendChild(indicator);
    });
    
    function goToSlide(index) {
        const slides = slideshow.querySelectorAll('.hero-slide');
        const dots = indicators.querySelectorAll('.hero-indicator');
        
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        
        currentSlide = index;
    }
    
    function nextSlide() {
        const next = (currentSlide + 1) % uniqueImages.length;
        goToSlide(next);
    }
    
    // Start slideshow (4 second interval)
    function startSlideshow() {
        slideInterval = setInterval(nextSlide, 4000);
    }
    
    // Pause on hover
    const heroSection = document.getElementById('heroSection');
    heroSection.addEventListener('mouseenter', function() {
        clearInterval(slideInterval);
    });
    
    heroSection.addEventListener('mouseleave', function() {
        startSlideshow();
    });
    
    startSlideshow();
});

// ============================================
// COUNTDOWN TIMER
// ============================================
function updateCountdown() {
    const now = new Date();
    const target = new Date();
    target.setHours(target.getHours() + 14);
    target.setMinutes(23);
    target.setSeconds(42);
    const diff = target - now;
    if(diff > 0) {
        document.getElementById('hours').textContent = Math.floor(diff / 3600000);
        document.getElementById('minutes').textContent = Math.floor((diff % 3600000) / 60000);
        document.getElementById('seconds').textContent = Math.floor((diff % 60000) / 1000);
    }
}
setInterval(updateCountdown, 1000);
updateCountdown();
</script>

</body>
</html>