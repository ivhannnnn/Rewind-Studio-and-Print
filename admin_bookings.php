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

/* ── Handle POST action ───────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF check */
    if (!hash_equals($_SESSION['admin_csrf'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid request token.'];
        header("Location: admin_bookings.php");
        exit();
    }

    $id     = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    /* Whitelist actions — "confirm" added since it was missing from the original switch */
    $allowed = ['approve', 'reject', 'confirm', 'finish'];
    if ($id <= 0 || !in_array($action, $allowed, true)) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid action.'];
        header("Location: admin_bookings.php");
        exit();
    }

    $map = [
        'approve' => "UPDATE bookings SET status='Approved', approved_at=NOW() WHERE id=?",
        'reject'  => "UPDATE bookings SET status='Rejected'                          WHERE id=?",
        'confirm' => "UPDATE bookings SET status='Confirmed'                         WHERE id=?",
        'finish'  => "UPDATE bookings SET status='Completed'                         WHERE id=?",
    ];

    $stmt = $conn->prepare($map[$action]);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    /* Rotate token after successful action */
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    $_SESSION['msg'] = ['type' => 'success',
        'text' => 'Booking #' . $id . ' updated to ' . ucfirst($action) . 'd.'];

    header("Location: admin_bookings.php");
    exit();
}

/* ── Fetch all bookings ───────────────────────────────────────────────── */
$bookings = $conn->query("
    SELECT b.*, u.full_name, u.email
    FROM   bookings b
    JOIN   users    u ON b.user_id = u.id
    ORDER  BY b.created_at DESC
");

/* ── Unread messages count (for sidebar badge) ────────────────────────── */
$unread_messages = (int) $conn->query(
    "SELECT COUNT(*) as c FROM messages WHERE status='unread'"
)->fetch_assoc()['c'];

$admin_name = htmlspecialchars($_SESSION['admin']['full_name'], ENT_QUOTES, 'UTF-8');

/* Helper: emit CSRF hidden + id + action in one line */
function csrf_fields(int $id, string $action): string {
    $token = htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">'
         . '<input type="hidden" name="id"         value="' . $id    . '">'
         . '<input type="hidden" name="action"     value="' . $action . '">';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings — Rewind Admin</title>
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
            <a href="admin_bookings.php" class="active">
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
                    <p class="topbar-eyebrow">Manage</p>
                    <h1 class="topbar-title">Bookings</h1>
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

        <!-- ── Booking cards ───────────────────────────────────────────── -->
        <?php if ($bookings->num_rows > 0): ?>

            <!-- Summary strip -->
            <div class="bookings-summary">
                <span><?= $bookings->num_rows ?> booking<?= $bookings->num_rows !== 1 ? 's' : '' ?> total</span>
            </div>

            <div class="booking-grid">

            <?php while ($row = $bookings->fetch_assoc()):
                $id     = (int) $row['id'];
                $status = strtolower($row['status']);
                $pstatus= strtolower($row['payment_status'] ?? '');
            ?>

                <article class="booking-card glass" aria-label="Booking #<?= $id ?>">

                    <!-- Card header -->
                    <div class="booking-header">
                        <div class="booking-id">
                            <span class="booking-id-label">Booking</span>
                            <span class="booking-id-num">#<?= $id ?></span>
                        </div>
                        <span class="status <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <!-- Divider -->
                    <hr class="card-divider">

                    <!-- Client info -->
                    <div class="booking-info">

                        <div class="info-row">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="info-row">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="info-row">
                            <i class="fas fa-briefcase" aria-hidden="true"></i>
                            <span><?= htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="info-row">
                            <i class="fas fa-calendar-day" aria-hidden="true"></i>
                            <span>
                                <?= htmlspecialchars(
                                    date('F j, Y', strtotime($row['event_date'])),
                                    ENT_QUOTES, 'UTF-8'
                                ) ?>
                            </span>
                        </div>

                    </div>

                    <!-- Pricing strip -->
                    <div class="pricing-strip">
                        <div class="price-item">
                            <span class="price-label">Package</span>
                            <strong class="price-value">
                                ₱<?= number_format($row['package_price'], 2) ?>
                            </strong>
                        </div>
                        <div class="price-divider"></div>
                        <div class="price-item">
                            <span class="price-label">Down payment</span>
                            <strong class="price-value price-value--gold">
                                ₱<?= number_format($row['down_payment'], 2) ?>
                            </strong>
                        </div>
                        <div class="price-divider"></div>
                        <div class="price-item">
                            <span class="price-label">Payment</span>
                            <strong class="price-value payment-status <?= htmlspecialchars(str_replace(' ', '-', $pstatus), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($row['payment_status'] ?? 'Unpaid', ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>
                    </div>

                    <!-- Footer meta -->
                    <p class="booking-meta">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        Booked <?= htmlspecialchars(
                            date('M j, Y · g:i A', strtotime($row['created_at'])),
                            ENT_QUOTES, 'UTF-8'
                        ) ?>
                    </p>

                    <!-- ── Actions ─────────────────────────────────────── -->
                    <div class="actions">

                        <?php if ($row['status'] === 'Pending'): ?>

                            <form method="POST">
                                <?= csrf_fields($id, 'approve') ?>
                                <button type="submit" class="btn approve">
                                    <i class="fas fa-check" aria-hidden="true"></i> Approve
                                </button>
                            </form>

                            <form method="POST"
                                  onsubmit="return confirm('Reject booking #<?= $id ?>?')">
                                <?= csrf_fields($id, 'reject') ?>
                                <button type="submit" class="btn reject">
                                    <i class="fas fa-times" aria-hidden="true"></i> Reject
                                </button>
                            </form>

                        <?php elseif ($row['status'] === 'Approved'): ?>

                            <?php if ($pstatus === 'unpaid'): ?>

                                <span class="waiting-payment">
                                    <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                                    Waiting for customer payment…
                                </span>

                            <?php elseif ($pstatus === 'pending verification'): ?>

                                <a class="btn verify"
                                   href="admin_payments.php?id=<?= $id ?>">
                                    <i class="fas fa-magnifying-glass-dollar" aria-hidden="true"></i>
                                    Verify Payment
                                </a>

                            <?php elseif ($pstatus === 'paid'): ?>

                                <form method="POST">
                                    <?= csrf_fields($id, 'confirm') ?>
                                    <button type="submit" class="btn approve">
                                        <i class="fas fa-circle-check" aria-hidden="true"></i>
                                        Confirm Booking
                                    </button>
                                </form>

                            <?php endif; ?>

                        <?php elseif ($row['status'] === 'Confirmed'): ?>

                            <form method="POST">
                                <?= csrf_fields($id, 'finish') ?>
                                <button type="submit" class="btn finish">
                                    <i class="fas fa-flag-checkered" aria-hidden="true"></i>
                                    Finish Event
                                </button>
                            </form>

                            <a class="btn message"
                               href="admin_chat.php?user_id=<?= (int)$row['user_id'] ?>&booking_id=<?= $id ?>">
                                <i class="fas fa-message" aria-hidden="true"></i>
                                Chat
                            </a>

                        <?php elseif ($row['status'] === 'Completed'): ?>

                            <span class="status completed" style="padding:9px 16px;">
                                <i class="fas fa-circle-check"></i> Event Complete
                            </span>

                        <?php elseif ($row['status'] === 'Rejected'): ?>

                            <span class="status rejected" style="padding:9px 16px;">
                                <i class="fas fa-circle-xmark"></i> Rejected
                            </span>

                        <?php endif; ?>

                    </div><!-- /.actions -->

                </article>

            <?php endwhile; ?>

            </div><!-- /.booking-grid -->

        <?php else: ?>
            <div class="empty">
                <i class="fas fa-calendar-xmark" style="font-size:2.5rem;margin-bottom:14px;opacity:.3"></i>
                <p>No bookings found.</p>
            </div>
        <?php endif; ?>

    </main>

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