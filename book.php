<?php
session_start();

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$service = $_GET['service'] ?? 'Selected Service';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Service - Rewind Studio</title>
    <link rel="stylesheet" href="booking.css">
</head>
<body>

<header>
    <div class="logo">
        <img src="486603552_1072841941528975_1520142067500954297_n.jpg">
        <span>Rewind Studio & Prints</span>
    </div>

    <nav class="nav-container">
        <span class="welcome-text">
            Welcome, <?php echo $_SESSION['user']['full_name']; ?>
        </span>
        <a href="services.php">Back to Services</a>
    </nav>
</header>

<section class="booking-section">

    <div class="booking-card">
        <h2>Book <?php echo htmlspecialchars($service); ?></h2>
<?php if(isset($_SESSION['booking_success'])): ?>
    <div class="glass-message" style="color:green;">
        <?php echo $_SESSION['booking_success']; unset($_SESSION['booking_success']); ?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['booking_error'])): ?>
    <div class="glass-message" style="color:red;">
        <?php echo $_SESSION['booking_error']; unset($_SESSION['booking_error']); ?>
    </div>
<?php endif; ?>
        <form action="process_booking.php" method="POST">
            <input type="hidden" name="service_name" value="<?php echo htmlspecialchars($service); ?>">

            <div class="form-group">
                <label>Event Date</label>
                <input type="date" name="event_date" required>
            </div>

            <div class="form-group">
                <label>Event Time</label>
                <input type="time" name="event_time" required>
            </div>

            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" required>
            </div>

            <div class="form-group">
                <label>Additional Notes</label>
                <textarea name="notes" rows="4"></textarea>
            </div>

            <button type="submit" class="glass-btn">Confirm Booking</button>
        </form>
    </div>

</section>

</body>
</html>