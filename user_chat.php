<?php
session_start();
include("db.php");

/* ── Auth guard ───────────────────────────────────────────────────────── */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id    = (int) $_SESSION['user']['id'];
$booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

/* ── Verify booking belongs to this user ─────────────────────────────── */
$own = $conn->prepare("
    SELECT id, service_name, status, payment_status, event_date, package_price, down_payment
    FROM bookings WHERE id=? AND user_id=? LIMIT 1
");
$own->bind_param("ii", $booking_id, $user_id);
$own->execute();
$booking = $own->get_result()->fetch_assoc();

if (!$booking) {
    header("Location: dashboard.php");
    exit();
}

/* ── CSRF ─────────────────────────────────────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ── Send user message ────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        header("Location: user_chat.php?booking_id=$booking_id");
        exit();
    }

    $message = trim($_POST['message'] ?? '');

    if (strlen($message) >= 1 && strlen($message) <= 2000) {
        $stmt = $conn->prepare("
            INSERT INTO messages (user_id, booking_id, message, sender, status, created_at)
            VALUES (?, ?, ?, 'user', 'unread', NOW())
        ");
        $stmt->bind_param("iis", $user_id, $booking_id, $message);
        $stmt->execute();
    }

    header("Location: user_chat.php?booking_id=$booking_id");
    exit();
}

/* ── Fetch user messages ──────────────────────────────────────────────── */
$stmt = $conn->prepare("
    SELECT id, message, created_at, 'user' AS sender, NULL AS admin_name
    FROM   messages
    WHERE  user_id=? AND booking_id=?
");
$stmt->bind_param("ii", $user_id, $booking_id);
$stmt->execute();
$user_msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── Fetch admin/bot replies ──────────────────────────────────────────── */
$admin_replies = [];
$tbl = $conn->query("SHOW TABLES LIKE 'message_replies'");
if ($tbl && $tbl->num_rows > 0 && !empty($user_msgs)) {
    $ids          = array_column($user_msgs, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('i', count($ids));
    $rep_stmt     = $conn->prepare("
        SELECT admin_name, reply AS message, created_at, 'admin' AS sender
        FROM   message_replies
        WHERE  message_id IN ($placeholders)
        ORDER  BY created_at ASC
    ");
    $rep_stmt->bind_param($types, ...$ids);
    $rep_stmt->execute();
    $admin_replies = $rep_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* Bot always active as first responder */

/* ── Merge and sort chronologically ──────────────────────────────────── */
$thread = array_merge($user_msgs, $admin_replies);
usort($thread, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));

$service_name = htmlspecialchars($booking['service_name'] ?? 'Booking', ENT_QUOTES, 'UTF-8');
$csrf         = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
$user_name    = htmlspecialchars($_SESSION['user']['full_name'] ?? 'there', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat — Booking #<?= $booking_id ?> | Rewind Studio</title>
    <link rel="stylesheet" href="user_chatsv2.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ═══ TOP BAR ══════════════════════════════════════════════════════════ -->
<header class="chat-topbar">
    <a href="dashboard.php" class="topbar-back">
        <i class="fas fa-arrow-left"></i>
        <span>Dashboard</span>
    </a>
    <div class="chat-topbar-center">
        <div class="chat-avatar" aria-hidden="true">RS</div>
        <div>
            <p class="chat-service"><?= $service_name ?></p>
            <p class="chat-id">
                Booking #<?= $booking_id ?>
                &nbsp;<span class="bot-badge">
                    <i class="fas fa-robot"></i> AI Assistant
                </span>
            </p>
        </div>
    </div>
    <a href="user_messages.php" class="topbar-link">
        <i class="fas fa-inbox"></i>
        <span>Inbox</span>
    </a>
</header>

<!-- ═══ CHAT THREAD ══════════════════════════════════════════════════════ -->
<main class="chat-main">
<div class="chat-box" id="chatBox">

    <?php if (empty($thread)): ?>
        <div class="chat-empty">
            <i class="fas fa-comments" aria-hidden="true"></i>
            <p>No messages yet. Send the first one!</p>
        </div>
    <?php else: ?>

        <?php
        $prev_date = null;
        foreach ($thread as $row):
            $is_user  = $row['sender'] === 'user';
            $ts       = strtotime($row['created_at']);
            $day      = date('Y-m-d', $ts);
            $time_fmt = date('g:i A', $ts);
            $label    = $is_user ? 'You' : ($row['admin_name'] ?? 'Rewind Studio');
            $is_bot   = !$is_user && str_starts_with($row['admin_name'] ?? '', 'AI Assistant');
        ?>

            <?php if ($day !== $prev_date): $prev_date = $day; ?>
                <div class="date-divider">
                    <span><?php
                        $today     = date('F j, Y');
                        $yesterday = date('F j, Y', strtotime('-1 day'));
                        $d         = date('F j, Y', $ts);
                        echo htmlspecialchars(
                            $d === $today ? 'Today' : ($d === $yesterday ? 'Yesterday' : $d),
                            ENT_QUOTES, 'UTF-8'
                        );
                    ?></span>
                </div>
            <?php endif; ?>

            <div class="bubble-row <?= $is_user ? 'bubble-row--user' : 'bubble-row--admin' ?>">
                <?php if (!$is_user): ?>
                    <div class="bubble-avatar <?= $is_bot ? 'bubble-avatar--bot' : '' ?>"
                         aria-hidden="true">
                        <?= $is_bot ? '<i class="fas fa-robot"></i>' : 'RS' ?>
                    </div>
                <?php endif; ?>

                <div class="bubble <?= $is_user ? 'bubble--user' : ($is_bot ? 'bubble--bot' : 'bubble--admin') ?>">
                    <?= nl2br(htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8')) ?>
                    <span class="bubble-meta">
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        &middot; <?= $time_fmt ?>
                    </span>
                </div>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <!-- Typing indicator (shown while bot is thinking) -->
    <div class="bubble-row bubble-row--admin" id="typingIndicator" style="display:none">
        <div class="bubble-avatar bubble-avatar--bot" aria-hidden="true">
            <i class="fas fa-robot"></i>
        </div>
        <div class="bubble bubble--bot typing-bubble">
            <span class="typing-dot"></span>
            <span class="typing-dot"></span>
            <span class="typing-dot"></span>
        </div>
    </div>

</div>
</main>

<!-- ═══ INPUT ════════════════════════════════════════════════════════════ -->
<footer class="chat-footer">
    <p class="bot-notice">
        <i class="fas fa-robot"></i>
        Our AI assistant replies instantly. Your admin can also follow up with a personal response.
    </p>
    <form class="chat-form" id="chatForm" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
        <textarea
            name="message"
            id="msgInput"
            class="chat-input"
            placeholder="Type a message…"
            maxlength="2000"
            rows="1"
            required
            aria-label="Message input"
        ></textarea>
        <button type="submit" class="chat-send" aria-label="Send message" id="sendBtn">
            <i class="fas fa-paper-plane" aria-hidden="true"></i>
        </button>
    </form>
</footer>

<!-- Pass PHP data to JS -->
<script>
const ADMIN_ACTIVE  = false; /* bot always on */
const BOOKING_ID    = <?= $booking_id ?>;
const USER_NAME     = <?= json_encode($_SESSION['user']['full_name'] ?? 'Customer') ?>;
const BOOKING_CTX   = <?= json_encode([
    'service'        => $booking['service_name'],
    'status'         => $booking['status'],
    'payment_status' => $booking['payment_status'],
    'event_date'     => $booking['event_date'],
    'package_price'  => $booking['package_price'],
    'down_payment'   => $booking['down_payment'],
]) ?>;
</script>
<script src="user_chat.js"></script>

</body>
</html>