<?php
session_start();

// If user is already logged in, redirect to dashboard
if(isset($_SESSION['user'])){
    header("Location: dashboard.php");
    exit();
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rewind Studio and Prints</title>
    <link rel="stylesheet" href="style_v2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>


<!-- ================= NAVIGATION ================= -->
<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg" alt="Logo">
        <span>Rewind Studio and Prints</span>
    </div>

    <nav>
        <a href="index.php">Home</a>
        <a href="services.php">Services</a>


    

        
            <a href="login.php">Login</a>
        
    </nav>
</header>

<!-- ================= HERO SECTION ================= -->
<section class="hero">
    <div class="hero-container">
        <div class="hero-content">
            <h1>Your Memories. <br> Printed to Perfection.</h1>
            <p>
                Welcome to Rewind Studio and Print — where we turn your favorite moments 
                into high-quality prints. Photo printing, tarpaulins, invitations, 
                and customized designs made with care and creativity.
            </p>
            <a href="services.php" class="hero-btn">Explore Services</a>
        </div>

        <div class="hero-image">
            <img src="logo.jpg" alt="Rewind Studio Prints">
        </div>
    </div>
</section>

<!-- ================= SAMPLE WORKS ================= -->
<section class="portfolio">
    <h2 class="section-title">Our Sample Works</h2>

    <div class="portfolio-container">

        <div class="portfolio-item">
            <img src="wed.png" alt="Wedding Shoot">
            <div class="portfolio-text">
                <h3>Wedding Photography</h3>
                <p>
                    Timeless and cinematic wedding coverage that captures 
                    every emotional and beautiful moment of your special day.
                </p>
            </div>
        </div>

        <div class="portfolio-item">
            <img src="new.png" alt="Studio Shoot">
            <div class="portfolio-text">
                <h3>Studio & Event Coverage</h3>
                <p>
                    High-quality portraits, debuts, graduations, and events 
                    professionally captured and printed by Rewind Studio.
                </p>
            </div>
        </div>

    </div>
</section>

<!-- ================= WHY CHOOSE US ================= -->
<section class="features">
    <h2 class="section-title">Why Choose Rewind Studio and Prints?</h2>

    <div class="features-grid">

        <div class="feature-card">
            <div class="icon">📸</div>
            <h3>Professional Team</h3>
            <p>Experienced photographers and editors dedicated to capturing your best moments.</p>
        </div>

        <div class="feature-card">
            <div class="icon">⏰</div>
            <h3>Flexible Schedule</h3>
            <p>Book sessions at your most convenient time for events and photoshoots.</p>
        </div>

        <div class="feature-card">
            <div class="icon">🖨️</div>
            <h3>High-Quality Prints</h3>
            <p>Sharp, vibrant prints using premium materials and modern printing technology.</p>
        </div>

        <div class="feature-card">
            <div class="icon">💎</div>
            <h3>Modern Equipment</h3>
            <p>Professional cameras, lighting, and gear for crystal-clear results.</p>
        </div>

        <div class="feature-card">
            <div class="icon">🎨</div>
            <h3>Creative Editing</h3>
            <p>Stylish retouching and cinematic edits to make your photos stand out.</p>
        </div>

    </div>
</section>

<!-- ================= FOOTER ================= -->
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
                <a href="#"><i class="fab fa-facebook-f"></i></a>
            </div>
        </div>

        <div class="footer-section">
            <h3>Business Hours</h3>
            <p>Mon – Sat: 8:00 AM – 6:00 PM</p>
            <p>Sunday: Closed</p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>© 2026 Rewind Studio Prints. All Rights Reserved.</p>
    </div>
</footer>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const msg = document.getElementById('logout-message');
    if(msg){
        setTimeout(() => {
            msg.classList.add('hide'); // fade out
            setTimeout(() => msg.remove(), 500); // remove from DOM after fade
        }, 3000); // 3 seconds
    }
});
</script>


</body>
</html>
