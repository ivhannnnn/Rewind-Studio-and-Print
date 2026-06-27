<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$service = htmlspecialchars($_GET['service'] ?? 'Selected Service');

$prices = [
    "Wedding"      => 15000,
    "Debut"        => 8000,
    "Graduation"   => 3000,
    "Pre-Birthday" => 3500,
    "Prenup"       => 10000,
];

$price       = $prices[$_GET['service'] ?? ''] ?? 0;
$downPayment = $price * 0.30;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Session — Rewind Studio</title>
    <link rel="stylesheet" href="bookingv2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ── Header ── -->
<header>
    <div class="logo">
        <img src="486603552_1072841941528975_1520142067500954297_n.jpg" alt="Rewind Studio logo">
        <span>Rewind Studio &amp; Prints</span>
    </div>

    <div class="nav-container">
        <span class="welcome-text">
            Welcome, <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>
        </span>
        <a href="services.php">
            <i class="fas fa-arrow-left" style="font-size:0.8rem;"></i> Back to Services
        </a>
    </div>
</header>

<!-- ── Booking form ── -->
<section class="booking-section">
<div class="booking-card">

    <h2>Book Your Session</h2>
    <p class="subtitle">
        Complete the form below. Your request will be reviewed by our team before payment is required.
    </p>

    <?php if (isset($_SESSION['booking_success'])): ?>
        <div class="glass-message success">
            <i class="fas fa-circle-check"></i>
            <?php echo $_SESSION['booking_success']; unset($_SESSION['booking_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['booking_error'])): ?>
        <div class="glass-message error">
            <i class="fas fa-circle-exclamation"></i>
            <?php echo $_SESSION['booking_error']; unset($_SESSION['booking_error']); ?>
        </div>
    <?php endif; ?>

    <!-- Package summary -->
    <div class="summary-card">
        <h3>Selected Package</h3>

        <div class="summary-row">
            <span>Package</span>
            <strong><?php echo $service; ?></strong>
        </div>

        <div class="summary-row">
            <span>Starting Price</span>
            <strong>₱<?php echo number_format($price, 2); ?></strong>
        </div>

        <div class="summary-row">
            <span>Required Down Payment (30%)</span>
            <strong class="highlight">₱<?php echo number_format($downPayment, 2); ?></strong>
        </div>
    </div>

    <!-- Booking process -->
    <div class="process-card">
        <h3>How it works</h3>

        <div class="step">
            <div class="step-num">1</div>
            Submit your booking request below
        </div>
        <div class="step">
            <div class="step-num">2</div>
            Wait for admin review and approval
        </div>
        <div class="step">
            <div class="step-num">3</div>
            Pay the required 30% down payment
        </div>
        <div class="step">
            <div class="step-num">4</div>
            Your session is confirmed — we'll see you there!
        </div>
    </div>

    <!-- Form -->
    <form action="process_booking.php" method="POST">
        <input type="hidden" name="service_name" value="<?php echo $service; ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="event_date">
                    <i class="fas fa-calendar" style="color:var(--gold);margin-right:6px;"></i>Event Date
                </label>
                <input
                    type="date"
                    id="event_date"
                    name="event_date"
                    min="<?php echo date('Y-m-d'); ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="event_time">
                    <i class="fas fa-clock" style="color:var(--gold);margin-right:6px;"></i>Event Time
                </label>
                <input
                    type="time"
                    id="event_time"
                    name="event_time"
                    required>
            </div>
        </div>

        <div class="form-group">
            <label for="location">
                <i class="fas fa-location-dot" style="color:var(--gold);margin-right:6px;"></i>Event Location
            </label>
            <input
                type="text"
                id="location"
                name="location"
                placeholder="Enter the venue or address"
                required>
        </div>

        <div class="form-group">
            <label for="notes">
                <i class="fas fa-pen-to-square" style="color:var(--gold);margin-right:6px;"></i>Additional Notes
            </label>
            <textarea
                id="notes"
                name="notes"
                rows="5"
                placeholder="Tell us more about your event — theme, special requests, etc."></textarea>
        </div>

        <button type="submit" class="glass-btn">
            <i class="fas fa-paper-plane" style="margin-right:8px;"></i>Submit Booking Request
        </button>
    </form>

</div>
</section>

</body>
</html>