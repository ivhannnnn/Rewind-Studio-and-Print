<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Our Services - Rewind Studio and Prints</title>
    <link rel="stylesheet" href="services.css">
</head>
<body>

<!-- HEADER -->
<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg" alt="Logo">
        <span>Rewind Studio and Prints</span>
    </div>

   <nav>
    <a href="<?php echo isset($_SESSION['user']) ? 'dashboard.php' : 'index.php'; ?>">Home</a>
    <a href="services.php">Services</a>
   

    <?php if(isset($_SESSION['user'])): ?>
     
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login</a>
    <?php endif; ?>
    
</nav>
</header>

<!-- SERVICES SECTION -->
<section class="services">
    <?php
    // List of services
    $services = [
        ["name"=>"Wedding", "desc"=>"Capture your special day with timeless photography and cinematic coverage tailored for your dream wedding."],
        ["name"=>"Debut", "desc"=>"Celebrate your 18th birthday with elegant portraits and full event coverage to preserve every magical moment."],
        ["name"=>"Graduation", "desc"=>"Professional graduation photoshoots and event coverage to celebrate your academic achievement."],
        ["name"=>"Pre-Birthday", "desc"=>"Creative themed pre-birthday photoshoots designed to highlight your personality and style."],
        ["name"=>"Prenup", "desc"=>"Romantic and cinematic pre-nuptial shoots that beautifully tell your love story."]
    ];

    foreach($services as $service):
    ?>
        <div class="card">
            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
            <p><?php echo htmlspecialchars($service['desc']); ?></p>
            <h4>Starting at</h4>
            <?php if(isset($_SESSION['user'])): ?>
                <!-- User is logged in, go to book.php -->
                <a href="book.php?service=<?php echo urlencode($service['name']); ?>" class="btn">Book Appointment</a>
            <?php else: ?>
                <!-- User not logged in, redirect to login.php -->
                <a href="login.php" class="btn">Book Appointment</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>

</body>
</html>