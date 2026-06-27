<?php
session_start();
include("db.php");

/* ================= SECURITY CHECK ================= */
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin']['id'];

/* ================= FETCH MESSAGES ================= */
$messages = $conn->query("
    SELECT m.*, u.full_name, u.email
    FROM messages m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.created_at DESC
");

/* ================= UNREAD COUNT ================= */
$unread_count = $conn->query("
    SELECT COUNT(*) as total FROM messages WHERE status='Unread'
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications - Rewind Admin</title>

<link rel="stylesheet" href="admin_dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <img src="385319258_714193217393851_8500146797645932462_n (1).jpg">
            <h2>REWIND<br><span>ADMIN</span></h2>
        </div>

        <nav>
            <a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="admin_bookings.php"><i class="fas fa-calendar"></i> Bookings</a>
            
            <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
            <a class="active" href="admin_notifications.php">
                <i class="fas fa-bell"></i> Alerts
                <?php if($unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            
            
            <a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>

    </aside>

    <!-- MAIN -->
    <main class="main">

        <div class="topbar">
            <h1>Notifications Inbox</h1>
        </div>

        <div class="message-grid">

            <?php if($messages->num_rows > 0): ?>
                <?php while($row = $messages->fetch_assoc()): ?>

                <div class="message-card glass">

                    <!-- HEADER -->
                    <div class="message-header">
                        <h3><?php echo htmlspecialchars($row['full_name']); ?></h3>
                        <span class="email"><?php echo htmlspecialchars($row['email']); ?></span>
                    </div>

                    <!-- MESSAGE -->
                    <div class="message-body">
                        <p><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                        <small><?php echo $row['created_at']; ?></small>
                    </div>

                    <!-- REPLIES (FIXED SAFE QUERY INSIDE LOOP) -->
                    <div class="replies">

                        <?php
                        $stmt = $conn->prepare("
                            SELECT reply_text, created_at 
                            FROM admin_replies 
                            WHERE message_id=? 
                            ORDER BY created_at ASC
                        ");
                        $stmt->bind_param("i", $row['id']);
                        $stmt->execute();
                        $replies = $stmt->get_result();

                        while($rep = $replies->fetch_assoc()):
                        ?>

                            <div class="reply-box">
                                <i class="fas fa-reply"></i>
                                <p><?php echo htmlspecialchars($rep['reply_text']); ?></p>
                                <span><?php echo $rep['created_at']; ?></span>
                            </div>

                        <?php endwhile; ?>

                    </div>

                    <!-- REPLY FORM -->
                    <form action="reply_message.php" method="POST" class="reply-form">

                        <input type="hidden" name="message_id" value="<?php echo $row['id']; ?>">

                        <textarea name="reply_text" placeholder="Type reply..." required></textarea>

                        <button type="submit" class="btn message">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>

                    </form>

                </div>

                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty">No messages found.</p>
            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>