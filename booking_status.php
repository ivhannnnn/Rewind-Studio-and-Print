<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

include("db.php"); // your DB connection

$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Status - Rewind Studio</title>
    <link rel="stylesheet" href="booking_status.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg" alt="Logo">
        <span>Rewind Studio and Prints</span>
    </div>

    <?php if(isset($_SESSION['user'])): ?>
        <div class="welcome-container">
            <h2>Welcome, <?php echo $_SESSION['user']['full_name']; ?>!</h2>
        </div>
    <?php endif; ?>

    <nav>
        <a href="dashboard.php">Home</a>
        <a href="booking_status.php">Booking Status</a>
        <a href="logout.php" onclick="return confirm('Logout?')">Logout</a>
    </nav>
</header>

<section class="hero">
    <div class="booking-container">
        <h1>Your Bookings</h1>

        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="booking-card">
                    <p><strong>Booking ID:</strong> <?php echo $row['id']; ?></p>
                    <p><strong>Service:</strong> <?php echo $row['service_name']; ?></p>
                    <p><strong>Date:</strong> <?php echo $row['created_at']; ?></p>
                    <p class="status <?php echo $row['status']; ?>"><?php echo $row['status']; ?></p>

                    <!-- Message Admin Button -->
                    <form action="message_admin.php" method="GET" style="margin-top:10px;">
                        <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn-message">Message Admin</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center;">You have no bookings yet.</p>
        <?php endif; ?>
    </div>
</section>

</body>
</html>