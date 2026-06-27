<?php
session_start();
include("db.php");

// ── Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// ── Safe session data ────────────────────────────────────────────────────────
$userName = $_SESSION['user']['full_name'] ?? 'Guest';

// ── Reviews query (limited to 6, with error handling) ────────────────────────
$reviews     = null;
$reviewCount = 0;

$reviewQuery = mysqli_query($conn, "
    SELECT
        r.rating,
        r.feedback,
        u.full_name,
        b.service_name,
        r.created_at
    FROM reviews r
    INNER JOIN bookings b ON r.booking_id = b.id
    INNER JOIN users  u ON b.user_id    = u.id
    ORDER BY r.created_at DESC
    LIMIT 6
");

if (!$reviewQuery) {
    error_log("Reviews query failed: " . mysqli_error($conn));
} else {
    $reviews     = $reviewQuery;
    $reviewCount = mysqli_num_rows($reviewQuery);
}

$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewind Studio and Prints</title>
    <link rel="icon" type="image/jpeg" href="logo.jpg">
    <link rel="stylesheet" href="dashboard_v12.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ═══════════════════════════ HEADER ═══════════════════════════════════════ -->
<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg"
             alt="Rewind Studio and Prints Logo">
        <span>Rewind Studio and Prints</span>
    </div>

    <div class="welcome-container">
        <h2>Welcome, <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>!</h2>
    </div>

    <nav class="nav" aria-label="Main navigation">
        <a href="dashboard.php"
           class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'dashboard.php' ? 'page' : 'false' ?>">
            Home
        </a>
        <a href="services.php"
           class="<?= $current === 'services.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'services.php' ? 'page' : 'false' ?>">
            Services
        </a>
        <a href="user_messages.php"
           class="<?= $current === 'user_messages.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'user_messages.php' ? 'page' : 'false' ?>">
            Messages
        </a>
        <a href="booking_status.php"
           class="<?= $current === 'booking_status.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'booking_status.php' ? 'page' : 'false' ?>">
            Booking Status
        </a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
</header>

<!-- ═══════════════════════════ HERO ═════════════════════════════════════════ -->
<section class="hero">
    <div class="hero-container">
        <div class="hero-content">
            <h1>Your Memories.<br>Printed to Perfection.</h1>
            <p>
                Welcome to Rewind Studio and Prints — where we turn your
                favourite moments into high-quality prints. Photo printing,
                tarpaulins, invitations, and customised designs made with
                care and creativity.
            </p>
            <a href="services.php" class="hero-btn">Explore Services</a>
        </div>
        <div class="hero-image">
            <img src="logo.jpg" alt="Rewind Studio Prints showcase">
        </div>
    </div>
</section>

<!-- ═══════════════════════════ SAMPLE WORKS ═════════════════════════════════ -->
<section class="portfolio">
    <h2 class="section-title">Our Sample Works</h2>

    <div class="portfolio-container">
        <div class="portfolio-item">
            <img src="wed.png" alt="Wedding photography sample by Rewind Studio">
            <div class="portfolio-text">
                <h3>Wedding Photography</h3>
                <p>
                    Timeless and cinematic wedding coverage that captures
                    every emotional and beautiful moment of your special day.
                </p>
            </div>
        </div>

        <div class="portfolio-item">
            <img src="new.png" alt="Studio and event coverage sample by Rewind Studio">
            <div class="portfolio-text">
                <h3>Studio &amp; Event Coverage</h3>
                <p>
                    High-quality portraits, debuts, graduations, and events
                    professionally captured and printed by Rewind Studio.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════ WHY CHOOSE US ════════════════════════════════ -->
<section class="features">
    <h2 class="section-title">Why Choose Rewind Studio and Prints?</h2>

    <div class="features-grid">
        <div class="feature-card">
            <div class="icon" aria-hidden="true">📸</div>
            <h3>Professional Team</h3>
            <p>Experienced photographers and editors dedicated to capturing your best moments.</p>
        </div>
        <div class="feature-card">
            <div class="icon" aria-hidden="true">⏰</div>
            <h3>Flexible Schedule</h3>
            <p>Book sessions at your most convenient time for events and photoshoots.</p>
        </div>
        <div class="feature-card">
            <div class="icon" aria-hidden="true">🖨️</div>
            <h3>High-Quality Prints</h3>
            <p>Sharp, vibrant prints using premium materials and modern printing technology.</p>
        </div>
        <div class="feature-card">
            <div class="icon" aria-hidden="true">💎</div>
            <h3>Modern Equipment</h3>
            <p>Professional cameras, lighting, and gear for crystal-clear results.</p>
        </div>
        <div class="feature-card">
            <div class="icon" aria-hidden="true">🎨</div>
            <h3>Creative Editing</h3>
            <p>Stylish retouching and cinematic edits to make your photos stand out.</p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════ REVIEWS ══════════════════════════════════════ -->
<section class="reviews-section">
    <h2 class="section-title">What Our Customers Say</h2>

    <div class="reviews-grid">
        <?php if ($reviews && $reviewCount > 0): ?>
            <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                <div class="review-card">

                    <!-- Star rating -->
                    <div class="review-stars" aria-label="Rating: <?= (int)$review['rating'] ?> out of 5">
                        <?php
                        $rating = (int)$review['rating'];
                        echo str_repeat('<i class="fas fa-star"  aria-hidden="true"></i>', $rating);
                        echo str_repeat('<i class="far fa-star"  aria-hidden="true"></i>', 5 - $rating);
                        ?>
                    </div>

                    <!-- Feedback -->
                    <p class="review-feedback">
                        "<?php echo htmlspecialchars($review['feedback'], ENT_QUOTES, 'UTF-8'); ?>"
                    </p>

                    <!-- Reviewer name -->
                    <h4>— <?php echo htmlspecialchars($review['full_name'], ENT_QUOTES, 'UTF-8'); ?></h4>

                    <!-- Service booked (now actually displayed) -->
                    <p class="review-service">
                        <i class="fas fa-camera" aria-hidden="true"></i>
                        <?php echo htmlspecialchars($review['service_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="review-empty">
                <h3>No Reviews Yet</h3>
                <p>Customer reviews will appear here after ratings are submitted.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════ FOOTER ═══════════════════════════════════════ -->
<footer class="footer">
    <div class="footer-container">

        <div class="footer-section">
            <h3>Contact Us</h3>
            <p>📍 2F Building, Manga District, Tagbilaran City, Bohol, Philippines, 6343</p>
            <p>📞 0955-382-6475</p>
            <p>✉️ rewindstudio88@gmail.com</p>
        </div>

        <div class="footer-section center">
            <h2>Rewind Studio and Prints</h2>
            <p>Turning Your Moments Into Beautiful Prints.</p>
            <div class="social-icons">
                <!-- Replace # with actual Facebook URL -->
                <a href="https://www.facebook.com/rewindstudio"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Visit our Facebook page">
                    <i class="fab fa-facebook-f"></i>
                </a>
            </div>
        </div>

        <div class="footer-section">
            <h3>Business Hours</h3>
            <p>Mon – Sat: 8:00 AM – 6:00 PM</p>
            <p>Sunday: Closed</p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>© <?php echo date('Y'); ?> Rewind Studio Prints. All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>