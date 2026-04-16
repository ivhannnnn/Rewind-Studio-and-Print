<?php
session_start();
include("db.php");

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

/* Get all chat threads per booking */
$chats = $conn->query("
    SELECT m.booking_id,
           MAX(m.created_at) as last_time,
           (SELECT message FROM messages 
            WHERE booking_id = m.booking_id 
            ORDER BY created_at DESC LIMIT 1) as last_message
    FROM messages m
    WHERE m.user_id = $user_id
    GROUP BY m.booking_id
    ORDER BY last_time DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>My Messages</title>
<link rel="stylesheet" href="user_messages.css">

</head>
<body>

<h2>My Booking Messages</h2>

<div class="chat-container">

<?php while($row = $chats->fetch_assoc()): ?>
    <a href="user_chat.php?booking_id=<?php echo $row['booking_id']; ?>">

        <div class="chat-list">
            <strong>Booking #<?php echo $row['booking_id']; ?></strong>
            <small><?php echo htmlspecialchars($row['last_message']); ?></small>
        </div>

    </a>
<?php endwhile; ?>

</div>

<div class="bottom-nav">
    <a href="dashboard.php">⬅ Dashboard</a>
    <a href="user_messages.php">💬 Messages</a>
</div>
</div>
</body>
</html>