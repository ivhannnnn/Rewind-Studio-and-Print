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

/* ── Handle delete ────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    if (!hash_equals($_SESSION['admin_csrf'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid request token.'];
        header("Location: admin_users.php");
        exit();
    }

    $id = (int) $_POST['delete_id'];

    /* Double safety: never delete admins */
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND is_admin=0");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    /* Rotate token */
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    $_SESSION['msg'] = ['type' => 'success', 'text' => 'User #' . $id . ' deleted.'];

    header("Location: admin_users.php");
    exit();
}

/* ── Fetch non-admin users ────────────────────────────────────────────── */
$users = $conn->query("
    SELECT u.*,
           COUNT(b.id)  AS total_bookings,
           MAX(b.created_at) AS last_booking
    FROM   users u
    LEFT JOIN bookings b ON b.user_id = u.id
    WHERE  u.is_admin = 0
    GROUP  BY u.id
    ORDER  BY u.id DESC
");

/* ── Unread messages count (sidebar badge) ────────────────────────────── */
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
    <title>Users — Rewind Admin</title>
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
            <a href="admin_messages.php">
                <i class="fas fa-envelope" aria-hidden="true"></i><span>Messages</span>
                <?php if ($unread_messages > 0): ?>
                    <span class="badge" aria-label="<?= $unread_messages ?> unread">
                        <?= $unread_messages ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="admin_users.php" class="active">
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
                    <p class="topbar-eyebrow">Manage</p>
                    <h1 class="topbar-title">Users</h1>
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
            <?php $msg = $_SESSION['msg']; unset($_SESSION['msg']); ?>
            <div class="alert alert-<?= htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8') ?>"
                 role="alert" aria-live="polite">
                <i class="fas <?= $msg['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"
                   aria-hidden="true"></i>
                <?= htmlspecialchars($msg['text'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- ── User cards ──────────────────────────────────────────────── -->
        <?php if ($users && $users->num_rows > 0): ?>

            <div class="bookings-summary">
                <span><?= $users->num_rows ?> registered user<?= $users->num_rows !== 1 ? 's' : '' ?></span>
            </div>

            <div class="booking-grid">

            <?php while ($user = $users->fetch_assoc()):
                $uid = (int) $user['id'];
            ?>

                <article class="booking-card glass user-card"
                         aria-label="User <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Card header -->
                    <div class="booking-header">
                        <div class="booking-id">
                            <span class="booking-id-label">User</span>
                            <span class="booking-id-num">#<?= $uid ?></span>
                        </div>
                        <div class="user-avatar-sm" aria-hidden="true">
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        </div>
                    </div>

                    <hr class="card-divider">

                    <!-- User info -->
                    <div class="booking-info">
                        <div class="info-row">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                            <span>Joined <?= htmlspecialchars(
                                date('M j, Y', strtotime($user['created_at'] ?? 'now')),
                                ENT_QUOTES, 'UTF-8'
                            ) ?></span>
                        </div>
                    </div>

                    <!-- Booking stats strip -->
                    <div class="pricing-strip">
                        <div class="price-item">
                            <span class="price-label">Bookings</span>
                            <strong class="price-value">
                                <?= (int) $user['total_bookings'] ?>
                            </strong>
                        </div>
                        <div class="price-divider"></div>
                        <div class="price-item">
                            <span class="price-label">Last booking</span>
                            <strong class="price-value" style="font-size:.78rem;">
                                <?= $user['last_booking']
                                    ? htmlspecialchars(date('M j, Y', strtotime($user['last_booking'])), ENT_QUOTES, 'UTF-8')
                                    : '—' ?>
                            </strong>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="actions">
                        <form method="POST"
                              onsubmit="return confirm('Permanently delete <?= htmlspecialchars(addslashes($user['full_name']), ENT_QUOTES, 'UTF-8') ?>?\n\nAll their data will be removed. This cannot be undone.')">
                            <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
                            <input type="hidden" name="delete_id"   value="<?= $uid ?>">
                            <button type="submit" class="btn reject">
                                <i class="fas fa-trash-can" aria-hidden="true"></i>
                                Delete User
                            </button>
                        </form>
                    </div>

                </article>

            <?php endwhile; ?>

            </div>

        <?php else: ?>
            <div class="empty">
                <i class="fas fa-users-slash" style="font-size:2.5rem;margin-bottom:14px;opacity:.3"></i>
                <p>No registered users found.</p>
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