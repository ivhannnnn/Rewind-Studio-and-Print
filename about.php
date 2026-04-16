<?php
session_start();
if(isset($_SESSION['user'])){
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About | Rewind Studio and Prints</title>
    <link rel="stylesheet" href="about.css">
</head>
<body>

<!-- HEADER -->
<header>
    <div class="logo">
        <img src="logo.png" alt="Logo">
        <span>Rewind Studio and Prints</span>
    </div>

    <nav>
        <a href="index.php">Home</a>
        <a href="services.php">Services</a>
        <a href="about.php" class="active">About</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<!-- HERO SECTION -->
<section class="about-hero">
    <div class="overlay"></div>
    <div class="about-content">
        <h1>About Rewind Studio</h1>
        <p>
            We turn your favorite memories into high-quality prints that last a lifetime.
            Creativity, precision, and passion are at the heart of everything we do.
        </p>
    </div>
</section>

<!-- ABOUT DETAILS -->
<section class="about-section">
    <div class="container">

        <div class="about-card">
            <h2>Our Mission</h2>
            <p>
                To provide premium printing services that preserve your special moments
                with unmatched quality and artistic touch.
            </p>
        </div>

        <div class="about-card">
            <h2>Our Vision</h2>
            <p>
                To become the leading creative print studio known for reliability,
                innovation, and customer satisfaction.
            </p>
        </div>

        <div class="about-card">
            <h2>Why Choose Us?</h2>
            <ul>
                <li>✔ High-quality materials</li>
                <li>✔ Fast turnaround time</li>
                <li>✔ Affordable pricing</li>
                <li>✔ Custom designs available</li>
            </ul>
        </div>

    </div>
</section>

<!-- FOOTER -->
<footer>
    <p>© <?php echo date("Y"); ?> Rewind Studio and Prints. All Rights Reserved.</p>
</footer>

</body>
</html>
