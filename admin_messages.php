<?php
session_start();
include("db.php");

/* ── Security headers ─────────────────────────────────────────────────── */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

/* ── Auth guard ───────────────────────────────────────────────────────── */
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* ── CSRF token ───────────────────────────────────────────────────────── */
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

/* ── Handle POST actions ──────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['admin_csrf'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid request token.'];
        header("Location: admin_messages.php");
        exit();
    }

    $action     = $_POST['action']     ?? '';
    $message_id = (int) ($_POST['message_id'] ?? 0);

    if ($message_id > 0) {

        if ($action === 'mark_read') {
            $stmt = $conn->prepare("UPDATE messages SET status='read' WHERE id=?");
            $stmt->bind_param("i", $message_id);
            $stmt->execute();

        } elseif ($action === 'reply') {
            $reply_text = trim($_POST['reply_text'] ?? '');
            if (strlen($reply_text) >= 1 && strlen($reply_text) <= 2000) {
                $admin_id   = (int) $_SESSION['admin']['id'];
                $admin_name = $_SESSION['admin']['full_name'];
                $stmt = $conn->prepare("
                    INSERT INTO message_replies (message_id, admin_id, admin_name, reply, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("iiss", $message_id, $admin_id, $admin_name, $reply_text);
                $stmt->execute();

                /* Mark as read when replied */
                $stmt2 = $conn->prepare("UPDATE messages SET status='read' WHERE id=?");
                $stmt2->bind_param("i", $message_id);
                $stmt2->execute();
            }

        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM messages WHERE id=?");
            $stmt->bind_param("i", $message_id);
            $stmt->execute();
            $_SESSION['msg'] = ['type' => 'success', 'text' => 'Message deleted.'];
        }
    }

    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    header("Location: admin_messages.php");
    exit();
}

/* ── Fetch messages ───────────────────────────────────────────────────── */
$messages = $conn->query("
    SELECT m.*, u.full_name, u.email
    FROM   messages m
    JOIN   users    u ON m.user_id = u.id
    ORDER  BY m.created_at DESC
");

/* ── Pre-fetch replies (only if table exists) ─────────────────────────── */
$replies_map = [];
$table_check = $conn->query("SHOW TABLES LIKE 'message_replies'");
if ($table_check && $table_check->num_rows > 0) {
    $replies_res = $conn->query("
        SELECT r.*
        FROM   message_replies r
        ORDER  BY r.created_at ASC
    ");
    if ($replies_res) {
        while ($r = $replies_res->fetch_assoc()) {
            $replies_map[(int) $r['message_id']][] = $r;
        }
    }
}

/* ── Unread count ─────────────────────────────────────────────────────── */
$unread_messages = (int) $conn->query(
    "SELECT COUNT(*) as c FROM messages WHERE status='unread'"
)->fetch_assoc()['c'];

$admin_name = htmlspecialchars($_SESSION['admin']['full_name'], ENT_QUOTES, 'UTF-8');
$csrf       = htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages — Rewind Admin</title>
    <link rel="stylesheet" href="admin_dashboardv4.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="admin-layout">

    <!-- ═══ SIDEBAR ══════════════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar" aria-label="Admin navigation">
        <div class="brand">
            <img src="385319258_714193217393851_8500146797645932462_n (1).jpg"
                 alt="Rewind Studio logo">
            <h2>Rewind Studio</h2>
            <span>Admin Panel</span>
        </div>
        <nav>
            <a href="admin_dashboard.php">
                <i class="fas fa-chart-line" aria-hidden="true"></i><span>Dashboard</span>
            </a>
            <a href="admin_bookings.php">
                <i class="fas fa-calendar-check" aria-hidden="true"></i><span>Bookings</span>
            </a>
            <a href="admin_payments.php">
                <i class="fas fa-credit-card" aria-hidden="true"></i><span>Payments</span>
            </a>
            <a href="admin_messages.php" class="active">
                <i class="fas fa-envelope" aria-hidden="true"></i><span>Messages</span>
                <?php if ($unread_messages > 0): ?>
                    <span class="badge" aria-label="<?= $unread_messages ?> unread">
                        <?= $unread_messages ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="admin_users.php">
                <i class="fas fa-users" aria-hidden="true"></i><span>Users</span>
            </a>
            <a href="logout.php" class="nav-logout">
                <i class="fas fa-right-from-bracket" aria-hidden="true"></i><span>Log Out</span>
            </a>
        </nav>
    </aside>

    <!-- ═══ MAIN ═════════════════════════════════════════════════════════ -->
    <main class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle"
                        aria-label="Toggle navigation" aria-expanded="false">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                <div>
                    <p class="topbar-eyebrow">Inbox</p>
                    <h1 class="topbar-title">
                        Messages
                        <?php if ($unread_messages > 0): ?>
                            <span class="badge" style="font-size:0.7rem;vertical-align:middle;">
                                <?= $unread_messages ?> unread
                            </span>
                        <?php endif; ?>
                    </h1>
                </div>
            </div>
            <div class="admin-chip">
                <div class="admin-avatar" aria-hidden="true">
                    <?= strtoupper(substr($_SESSION['admin']['full_name'], 0, 1)) ?>
                </div>
                <span><?= $admin_name ?></span>
            </div>
        </div>

        <!-- Flash message -->
        <?php if (isset($_SESSION['msg'])): ?>
            <?php $flash = $_SESSION['msg']; unset($_SESSION['msg']); ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>"
                 role="alert" aria-live="polite">
                <i class="fas <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"
                   aria-hidden="true"></i>
                <?= htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- ── Message cards ───────────────────────────────────────────── -->
        <?php if ($messages && $messages->num_rows > 0): ?>

            <div class="bookings-summary">
                <span><?= $messages->num_rows ?> message<?= $messages->num_rows !== 1 ? 's' : '' ?></span>
            </div>

            <div class="message-grid">

            <?php while ($m = $messages->fetch_assoc()):
                $mid       = (int) $m['id'];
                $is_unread = $m['status'] === 'unread';
                $thread    = $replies_map[$mid] ?? [];
            ?>

                <article class="message-card glass <?= $is_unread ? 'message-card--unread' : '' ?>"
                         aria-label="Message from <?= htmlspecialchars($m['full_name'], ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Header -->
                    <div class="message-header">
                        <div class="msg-sender">
                            <div class="user-avatar-sm" aria-hidden="true">
                                <?= strtoupper(substr($m['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h3 class="msg-name">
                                    <?= htmlspecialchars($m['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                                <span class="msg-email">
                                    <?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </div>
                        <span class="status <?= htmlspecialchars($m['status'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($is_unread): ?>
                                <i class="fas fa-circle-dot"></i> Unread
                            <?php else: ?>
                                <i class="fas fa-check"></i> Read
                            <?php endif; ?>
                        </span>
                    </div>

                    <hr class="card-divider">

                    <!-- Message body -->
                    <div class="message-body">
                        <?= nl2br(htmlspecialchars($m['message'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>

                    <!-- Timestamp -->
                    <p class="booking-meta">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        <?= htmlspecialchars(
                            date('M j, Y · g:i A', strtotime($m['created_at'])),
                            ENT_QUOTES, 'UTF-8'
                        ) ?>
                    </p>

                    <!-- Existing replies -->
                    <?php if (!empty($thread)): ?>
                        <div class="replies" aria-label="Admin replies">
                            <?php foreach ($thread as $reply): ?>
                                <div class="reply-box">
                                    <i class="fas fa-reply" aria-hidden="true"></i>
                                    <?= nl2br(htmlspecialchars($reply['reply'], ENT_QUOTES, 'UTF-8')) ?>
                                    <span>
                                        <?= htmlspecialchars($reply['admin_name'], ENT_QUOTES, 'UTF-8') ?>
                                        &middot;
                                        <?= htmlspecialchars(
                                            date('M j, Y · g:i A', strtotime($reply['created_at'])),
                                            ENT_QUOTES, 'UTF-8'
                                        ) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- ── Actions: one form per action, NO nesting ────── -->

                    <!-- Reply form -->
                    <form class="reply-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="message_id" value="<?= $mid ?>">
                        <input type="hidden" name="action"     value="reply">
                        <textarea name="reply_text"
                                  placeholder="Write a reply…"
                                  rows="2"
                                  maxlength="2000"
                                  aria-label="Reply to <?= htmlspecialchars($m['full_name'], ENT_QUOTES, 'UTF-8') ?>"></textarea>
                        <div class="msg-actions">
                            <button type="submit" class="btn approve btn-sm">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i> Send Reply
                            </button>
                        </div>
                    </form>

                    <!-- Mark read form (separate) -->
                    <?php if ($is_unread): ?>
                        <form method="POST" class="msg-action-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="message_id" value="<?= $mid ?>">
                            <input type="hidden" name="action"     value="mark_read">
                            <div class="msg-actions">
                                <button type="submit" class="btn verify btn-sm">
                                    <i class="fas fa-check" aria-hidden="true"></i> Mark Read
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- Delete form (separate) -->
                    <form method="POST" class="msg-action-form"
                          onsubmit="return confirm('Delete this message permanently?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="message_id" value="<?= $mid ?>">
                        <input type="hidden" name="action"     value="delete">
                        <div class="msg-actions">
                            <button type="submit" class="btn reject btn-sm">
                                <i class="fas fa-trash-can" aria-hidden="true"></i> Delete
                            </button>
                        </div>
                    </form>

                </article>

            <?php endwhile; ?>

            </div>

        <?php else: ?>
            <div class="empty">
                <i class="fas fa-envelope-open" style="font-size:2.5rem;margin-bottom:14px;opacity:.3"></i>
                <p>No messages yet.</p>
            </div>
        <?php endif; ?>

    </main>

</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
toggle?.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open);
});
</script>

</body>
</html>