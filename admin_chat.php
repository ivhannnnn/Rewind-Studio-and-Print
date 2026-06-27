<?php
session_start();
include("db.php");

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$user_id = (int)$_GET['user_id'];
$booking_id = (int)$_GET['booking_id'];

/* ================= SEND MESSAGE ================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $message = trim($_POST['message']);

    if(!empty($message)){
        $stmt = $conn->prepare("
            INSERT INTO messages (user_id, booking_id, message, sender, created_at)
            VALUES (?, ?, ?, 'admin', NOW())
        ");

        $stmt->bind_param("iis", $user_id, $booking_id, $message);
        $stmt->execute();
    }

    header("Location: admin_chat.php?user_id=$user_id&booking_id=$booking_id");
    exit();
}

/* ================= GET CHAT ================= */
$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE user_id=? AND booking_id=? 
    ORDER BY created_at ASC
");

$stmt->bind_param("ii", $user_id, $booking_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Booking Chat</title>
<link rel="stylesheet" href="admin_chatv1.css">
</head>

<body>

<div class="top-bar">
    <a href="admin_dashboard.php">⬅ Dashboard</a>
    <a href="admin_messages.php">💬 Messages</a>
</div>

<div class="header">
    <h3>Chat for Booking #<?php echo $booking_id; ?></h3>
</div>

<div class="chat-box">

<?php while($row = $result->fetch_assoc()): ?>

    <div class="msg <?php echo $row['sender']; ?>">
        <?php echo htmlspecialchars($row['message']); ?>
        <br>
        <small><?php echo $row['created_at']; ?></small>
    </div>

<?php endwhile; ?>

</div>

<form method="POST">
    <input type="text" name="message" placeholder="Type message..." required>
    <button type="submit">Send</button>
</form>

</body>
</html>