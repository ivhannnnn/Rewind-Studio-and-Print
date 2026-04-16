<?php
session_start();
include("db.php");

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$booking_id = $_GET['booking_id'] ?? null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $booking_id = $_POST['booking_id'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO messages (user_id, booking_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $user_id, $booking_id, $message);
    $stmt->execute();

    $_SESSION['success'] = "Message sent to admin!";
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Admin</title>
    <link rel="stylesheet" href="booking.css">
</head>
<body>

<section class="booking-section">
    <div class="booking-card">
        <h2>Message Admin</h2>
        <form action="" method="POST">
            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
            <div class="form-group">
                <label>Your Message</label>
                <textarea name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="glass-btn">Send Message</button>
        </form>
    </div>
</section>

</body>
</html>