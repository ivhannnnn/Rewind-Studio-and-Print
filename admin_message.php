<?php
session_start();
include("db.php");

// Admin session check
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$messages = $conn->query("
    SELECT m.*, u.full_name, u.email
    FROM messages m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Messages - Admin</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>

<header>
    <div class="logo">Admin Panel</div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_bookings.php">Bookings</a>
        <a href="admin_users.php">Users</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="messages-section">
    <h2>User Messages</h2>

    <?php if($messages->num_rows > 0): ?>
        <?php while($msg = $messages->fetch_assoc()): ?>
            <div class="message-card">
                <p><strong>From:</strong> <?php echo $msg['full_name'] . " (" . $msg['email'] . ")"; ?></p>
                <p><strong>Message:</strong> <?php echo nl2br($msg['message']); ?></p>
                <p><strong>Sent at:</strong> <?php echo $msg['created_at']; ?></p>
                <p><strong>Status:</strong> <?php echo $msg['status']; ?></p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No messages found.</p>
    <?php endif; ?>
</section>

</body>
</html>