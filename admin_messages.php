<?php
session_start();
include("db.php");

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

/* Get latest message per booking */
$chats = $conn->query("
    SELECT m.booking_id, m.user_id, MAX(m.created_at) as last_time,
           u.full_name,
           (SELECT message FROM messages 
            WHERE booking_id = m.booking_id 
            ORDER BY created_at DESC LIMIT 1) as last_message
    FROM messages m
    JOIN users u ON m.user_id = u.id
    GROUP BY m.booking_id, m.user_id
    ORDER BY last_time DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Booking Messages</title>
<link rel="stylesheet" href="admin_messages.css">
</head>

<body>

<div class="top-bar">
    <a href="admin_dashboard.php">⬅ Dashboard</a>
    <a href="admin_messages.php">💬 Messages</a>
</div>

<h2>Booking Conversations</h2>

<div class="chat-container">

<?php while($row = $chats->fetch_assoc()): ?>

    <!-- FIX: no <a> tag, no blue link issue -->
    <div class="chat-list"
         onclick="window.location.href='admin_chat.php?user_id=<?php echo $row['user_id']; ?>&booking_id=<?php echo $row['booking_id']; ?>'">

        <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>

        <p>Booking #<?php echo $row['booking_id']; ?></p>

        <small><?php echo htmlspecialchars($row['last_message']); ?></small>

    </div>

<?php endwhile; ?>

</div>

</body>
</html>