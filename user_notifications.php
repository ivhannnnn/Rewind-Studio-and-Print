<?php
session_start();
include("db.php");

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

// ✅ Clear only admin replies for this user
if(isset($_POST['clear_all'])){
    $conn->query("
        DELETE ar 
        FROM admin_replies ar
        JOIN messages m ON ar.message_id = m.id
        WHERE m.user_id = $user_id
    ");
    header("Location: user_notifications.php");
    exit();
}

// Fetch admin replies
$replies = $conn->query("
    SELECT m.id AS message_id, m.message, ar.reply_text, ar.created_at AS reply_time
    FROM messages m
    LEFT JOIN admin_replies ar ON m.id = ar.message_id
    WHERE m.user_id = $user_id
    ORDER BY ar.created_at DESC
");

// Fetch booking notifications
$bookings = $conn->query("
    SELECT id, service_name, status, created_at
    FROM bookings
    WHERE user_id = $user_id
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Notifications - Rewind Studio</title>
    <link rel="stylesheet" href="user_notification1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg" alt="Logo">
        <span>Rewind Studio & Prints</span>
    </div>
    <nav>
        <a href="dashboard.php">Home</a>
        <a href="user_notification.php" class="active">Notifications</a>
        <a href="logout.php" onclick="return confirm('Logout?')">Logout</a>
    </nav>
</header>

<section class="notifications-container">
    <h2 class="section-title">Notifications</h2>

    <!-- Clear All Button -->
    <form method="POST">
        <button type="submit" name="clear_all" class="clear-btn">
            <i class="fas fa-trash-alt"></i> Clear All Admin Notifications
        </button>
    </form>

    <div class="notifications-scroll">
        <!-- Booking Notifications -->
        <?php if($bookings->num_rows > 0): ?>
            <?php while($row = $bookings->fetch_assoc()): ?>
                <div class="notification-card">
                    <p><strong>Booking:</strong> <?php echo htmlspecialchars($row['service_name']); ?></p>
                    <p><strong>Date:</strong> <?php echo $row['created_at']; ?></p>
                    <p class="status <?php echo $row['status']; ?>"><?php echo $row['status']; ?></p>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Admin Replies -->
        <?php if($replies->num_rows > 0): ?>
            <?php while($row = $replies->fetch_assoc()): ?>
                <?php if(!empty($row['reply_text'])): ?>
                    <div class="notification-card">
                        <p><strong>Admin Reply:</strong></p>
                        <div class="reply-box">
                            <p><?php echo htmlspecialchars($row['reply_text']); ?></p>
                            <small>Sent on: <?php echo $row['reply_time']; ?></small>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#fff;">No notifications yet.</p>
        <?php endif; ?>
    </div>
</section>

</body>
</html>