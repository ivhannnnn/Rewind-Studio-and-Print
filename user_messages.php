<?php
session_start();
include("db.php");

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['name'] ?? 'User';

$chats = $conn->query("
    SELECT m.booking_id,
           MAX(m.created_at) AS last_time,
           (SELECT message FROM messages
            WHERE booking_id = m.booking_id
            ORDER BY created_at DESC LIMIT 1) AS last_message
    FROM messages m
    WHERE m.user_id = $user_id
    GROUP BY m.booking_id
    ORDER BY last_time DESC
");

function timeAgo($datetime) {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->i < 1 && $diff->h == 0 && $diff->d == 0) return 'Just now';
    if ($diff->h == 0 && $diff->d == 0) return $diff->i . 'm ago';
    if ($diff->d == 0) return $diff->h . 'h ago';
    if ($diff->d == 1) return 'Yesterday';
    if ($diff->d < 7)  return $diff->d . 'd ago';
    return $ago->format('M j');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages — Rewind Studio</title>
    <link rel="stylesheet" href="user_messagesv3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ── Header ── -->
<header>
    <div class="logo">
        <img src="logo.jpg" alt="Rewind Studio logo">
        <span>Rewind Studio</span>
    </div>

    <nav class="nav" aria-label="Main navigation">
        <a href="dashboard.php"
           class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'dashboard.php' ? 'page' : 'false' ?>">
            Home
        </a>
        <a href="services.php"
           class="<?= $current === 'services.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'services.php' ? 'page' : 'false' ?>">
            Services
        </a>
        <a href="user_messages.php"
           class="<?= $current === 'user_messages.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'user_messages.php' ? 'page' : 'false' ?>">
            Messages
        </a>
        <a href="booking_status.php"
           class="<?= $current === 'booking_status.php' ? 'active' : '' ?>"
           aria-current="<?= $current === 'booking_status.php' ? 'page' : 'false' ?>">
            Booking Status
        </a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
</header>

<!-- ── Page title ── -->
<div class="page-header">
    <h1>My Messages</h1>
    <p>Your conversations with Rewind Studio</p>
</div>

<!-- ── Chat list ── -->
<div class="chat-container">

    <?php if ($chats->num_rows === 0): ?>
        <div class="empty-state">
            <div class="icon"><i class="far fa-comment-dots"></i></div>
            <h3>No messages yet</h3>
            <p>Once you make a booking, your conversation thread will appear here.</p>
        </div>

    <?php else: ?>
        <?php while ($row = $chats->fetch_assoc()):
            $preview  = htmlspecialchars($row['last_message'] ?? '—');
            $timeAgo  = timeAgo($row['last_time']);
            $bookingId = (int)$row['booking_id'];
        ?>

        <a class="chat-link" href="user_chat.php?booking_id=<?php echo $bookingId; ?>">
            <div class="chat-list">

                <!-- Avatar -->
                <div class="chat-avatar">
                    <i class="fas fa-camera"></i>
                </div>

                <!-- Info -->
                <div class="chat-info">
                    <strong>Booking #<?php echo $bookingId; ?></strong>
                    <small><?php echo $preview; ?></small>
                </div>

                <!-- Meta -->
                <div class="chat-meta">
                    <span class="chat-time"><?php echo $timeAgo; ?></span>
                    <span class="chat-arrow"><i class="fas fa-chevron-right"></i></span>
                </div>

            </div>
        </a>

        <?php endwhile; ?>
    <?php endif; ?>

</div>

<!-- ── Bottom nav ── -->


</body>
</html>