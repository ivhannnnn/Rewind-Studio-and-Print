<?php
session_start();
include("db.php");

/* ── Auth guard ───────────────────────────────────────────────────────── */
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* ── CSRF token ───────────────────────────────────────────────────────── */
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

/* ── Danger: clear all data ───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all'])) {

    /* CSRF check */
    if (!hash_equals($_SESSION['admin_csrf'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid request token.'];
        header("Location: admin_dashboard.php");
        exit();
    }

    /* Rotate token after use */
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));

    $conn->query("DELETE FROM messages");
    $conn->query("DELETE FROM bookings");

    $_SESSION['msg'] = ['type' => 'success', 'text' => 'System data cleared successfully.'];
    header("Location: admin_dashboard.php");
    exit();
}

$admin_name = htmlspecialchars($_SESSION['admin']['full_name'], ENT_QUOTES, 'UTF-8');

/* ── Stats (single query is cheaper but keeping individual for clarity) ── */
$total_bookings    = (int) $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
$pending_bookings  = (int) $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='Pending'")->fetch_assoc()['c'];
$confirmed_bookings= (int) $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='Confirmed'")->fetch_assoc()['c'];
$completed_bookings= (int) $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='Completed'")->fetch_assoc()['c'];
$rejected_bookings = (int) $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='Rejected'")->fetch_assoc()['c'];
$total_users       = (int) $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$unread_messages   = (int) $conn->query("SELECT COUNT(*) as c FROM messages WHERE status='unread'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Rewind Studio</title>
    <link rel="stylesheet" href="admin_dashboardv3.css">
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
            <a href="admin_dashboard.php" class="active">
                <i class="fas fa-chart-line" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_bookings.php">
                <i class="fas fa-calendar-check" aria-hidden="true"></i>
                <span>Bookings</span>
            </a>
            <a href="admin_payments.php">
                <i class="fas fa-credit-card" aria-hidden="true"></i>
                <span>Payments</span>
            </a>
            <a href="admin_messages.php">
                <i class="fas fa-envelope" aria-hidden="true"></i>
                <span>Messages</span>
                <?php if ($unread_messages > 0): ?>
                    <span class="badge" aria-label="<?= $unread_messages ?> unread">
                        <?= $unread_messages ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="admin_users.php">
                <i class="fas fa-users" aria-hidden="true"></i>
                <span>Users</span>
            </a>
            <a href="logout.php" class="nav-logout">
                <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
                <span>Log Out</span>
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
                    <p class="topbar-eyebrow">Overview</p>
                    <h1 class="topbar-title">Dashboard</h1>
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

        <!-- ── Stats grid ──────────────────────────────────────────────── -->
        <section aria-label="Booking statistics">
            <p class="section-eyebrow">At a glance</p>
            <div class="stats">

                <div class="stat-card">
                    <div class="stat-icon stat-icon--blue">
                        <i class="fas fa-calendar-days" aria-hidden="true"></i>
                    </div>
                    <div class="stat-body">
                        <span class="stat-label">Total Bookings</span>
                        <strong class="stat-value"><?= $total_bookings ?></strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon--yellow">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                    </div>
                    <div class="stat-body">
                        <span class="stat-label">Pending</span>
                        <strong class="stat-value"><?= $pending_bookings ?></strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon--green">
                        <i class="fas fa-circle-check" aria-hidden="true"></i>
                    </div>
                    <div class="stat-body">
                        <span class="stat-label">Confirmed</span>
                        <strong class="stat-value"><?= $confirmed_bookings ?></strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon--gold">
                        <i class="fas fa-flag-checkered" aria-hidden="true"></i>
                    </div>
                    <div class="stat-body">
                        <span class="stat-label">Completed</span>
                        <strong class="stat-value"><?= $completed_bookings ?></strong>
                    </div>
                </div>

                <div class="stat-card stat-card--danger">
                    <div class="stat-icon stat-icon--red">
                        <i class="fas fa-circle-xmark" aria-hidden="true"></i>
                    </div>
                    <div class="stat-body">
                        <span class="stat-label">Rejected</span>
                        <strong class="stat-value"><?= $rejected_bookings ?></strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon--purple">
                        <i class="fas fa-users" aria-hidden="true"></i>
                    </div>
                    <div class="stat-body">
                        <span class="stat-label">Registered Users</span>
                        <strong class="stat-value"><?= $total_users ?></strong>
                    </div>
                </div>

            </div>
        </section>

        <!-- ── Quick links ─────────────────────────────────────────────── -->
        <section aria-label="Quick actions" class="quick-links">
            <p class="section-eyebrow">Quick actions</p>
            <div class="quick-grid">
                <a href="admin_bookings.php" class="quick-card">
                    <i class="fas fa-calendar-check" aria-hidden="true"></i>
                    <span>Manage Bookings</span>
                </a>
                <a href="admin_payments.php" class="quick-card">
                    <i class="fas fa-receipt" aria-hidden="true"></i>
                    <span>Verify Payments</span>
                </a>
                <a href="admin_messages.php" class="quick-card">
                    <i class="fas fa-envelope-open-text" aria-hidden="true"></i>
                    <span>Read Messages</span>
                    <?php if ($unread_messages > 0): ?>
                        <span class="badge"><?= $unread_messages ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_users.php" class="quick-card">
                    <i class="fas fa-user-gear" aria-hidden="true"></i>
                    <span>Manage Users</span>
                </a>
            </div>
        </section>

        <!-- ── Danger zone ─────────────────────────────────────────────── -->
        <section class="danger-zone" aria-labelledby="danger-heading">
            <div class="danger-inner">
                <div class="danger-text">
                    <h2 id="danger-heading">
                        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                        Danger Zone
                    </h2>
                    <p>Permanently deletes <strong>all bookings and messages</strong>. This cannot be undone.</p>
                </div>
                <form method="POST"
                      onsubmit="return confirm('Delete ALL bookings and messages permanently?\n\nThis action cannot be undone.')">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" name="clear_all" class="btn-danger">
                        <i class="fas fa-trash-can" aria-hidden="true"></i>
                        Clear System Data
                    </button>
                </form>
            </div>
        </section>

    </main><!-- /.main -->

</div><!-- /.admin-layout -->

<script>
/* Mobile sidebar toggle */
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');

toggle?.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open);
});
</script>

</body>
</html>