<?php
session_start();
include("db.php");

// Admin session check
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin']['id'];
$admin_name = $_SESSION['admin']['full_name'];

// Fetch all messages with possible admin replies
$messages = $conn->query("
    SELECT m.*, u.full_name, u.email
    FROM messages m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.created_at DESC
");

// Count unread messages for navigation badge
$unread_count = $conn->query("SELECT COUNT(*) as total FROM messages WHERE status='Unread'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Notifications - Rewind Studio</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg" alt="Logo">
        <span>Admin Panel</span>
    </div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_bookings.php">Manage Bookings</a>
        <a href="admin_users.php">Manage Users</a>
        <a href="admin_notifications.php">Notifications 
            <?php if($unread_count > 0) echo "<span class='badge'>{$unread_count}</span>"; ?>
        </a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="hero">
    <h2>Notifications</h2>

    <div class="messages-container">
        <?php if($messages->num_rows > 0): ?>
            <?php while($row = $messages->fetch_assoc()): ?>
                <div class="message-card">
                    <p><strong>From:</strong> <?php echo htmlspecialchars($row['full_name'] . " ({$row['email']})"); ?></p>
                    <p><strong>Message:</strong> <?php echo htmlspecialchars($row['message']); ?></p>
                    <p><strong>Received:</strong> <?php echo $row['created_at']; ?></p>

                    <!-- Fetch admin replies for this message -->
                    <?php
                        $replies = $conn->query("SELECT * FROM admin_replies WHERE message_id={$row['id']} ORDER BY created_at ASC");
                        if($replies->num_rows > 0):
                            while($rep = $replies->fetch_assoc()):
                    ?>
                        <div class="admin-reply">
                            <p><strong>Admin Reply:</strong> <?php echo htmlspecialchars($rep['reply_text']); ?></p>
                            <p class="reply-time"><?php echo $rep['created_at']; ?></p>
                        </div>
                    <?php 
                            endwhile;
                        endif;
                    ?>

                    <!-- Reply form -->
                    <form action="reply_message.php" method="POST">
                        <input type="hidden" name="message_id" value="<?php echo $row['id']; ?>">
                        <textarea name="reply_text" rows="2" placeholder="Type your reply here"></textarea>
                        <button type="submit" class="glass-btn">Send Reply</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No messages yet.</p>
        <?php endif; ?>
    </div>
</section>

</body>
</html>