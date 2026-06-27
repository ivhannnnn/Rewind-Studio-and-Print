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

/* ── Handle inline approve / reject ──────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['admin_csrf'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid request token.'];
        header("Location: admin_payments.php");
        exit();
    }

    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $action     = $_POST['action'] ?? '';

    if ($booking_id > 0 && in_array($action, ['approve', 'reject'], true)) {

        if ($action === 'approve') {
            $stmt = $conn->prepare(
                "UPDATE bookings SET payment_status='Paid' WHERE id=?"
            );
        } else {
            $stmt = $conn->prepare(
                "UPDATE bookings SET payment_status='Unpaid', receipt_image=NULL WHERE id=?"
            );
        }

        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        /* Rotate token */
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['msg'] = [
            'type' => $action === 'approve' ? 'success' : 'error',
            'text' => 'Payment #' . $booking_id . ' ' . ($action === 'approve' ? 'approved.' : 'rejected.'),
        ];
    }

    header("Location: admin_payments.php");
    exit();
}

/* ── Fetch bookings that have a receipt or are pending verification ────── */
$result = $conn->query("
    SELECT b.*, u.full_name, u.email
    FROM   bookings b
    JOIN   users    u ON b.user_id = u.id
    WHERE  b.payment_status IN ('Pending Verification','Paid','Unpaid')
    ORDER  BY b.created_at DESC
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
    <title>Payments — Rewind Admin</title>
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
            <a href="admin_dashboard.php">
                <i class="fas fa-chart-line" aria-hidden="true"></i><span>Dashboard</span>
            </a>
            <a href="admin_bookings.php">
                <i class="fas fa-calendar-check" aria-hidden="true"></i><span>Bookings</span>
            </a>
            <a href="admin_payments.php" class="active">
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
                    <p class="topbar-eyebrow">Finance</p>
                    <h1 class="topbar-title">Payment Approvals</h1>
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

        <!-- ── Payment cards ───────────────────────────────────────────── -->
        <?php if ($result && $result->num_rows > 0): ?>

            <div class="bookings-summary">
                <span><?= $result->num_rows ?> payment record<?= $result->num_rows !== 1 ? 's' : '' ?></span>
            </div>

            <div class="booking-grid">

            <?php while ($row = $result->fetch_assoc()):
                $id      = (int) $row['id'];
                $pstatus = strtolower(str_replace(' ', '-', $row['payment_status'] ?? 'unpaid'));
            ?>

                <article class="booking-card glass" aria-label="Payment for booking #<?= $id ?>">

                    <!-- Card header -->
                    <div class="booking-header">
                        <div class="booking-id">
                            <span class="booking-id-label">Booking</span>
                            <span class="booking-id-num">#<?= $id ?></span>
                        </div>
                        <span class="payment-status <?= htmlspecialchars($pstatus, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($row['payment_status'] ?? 'Unpaid', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

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
                            <span><?= htmlspecialchars(
                                date('F j, Y', strtotime($row['event_date'])),
                                ENT_QUOTES, 'UTF-8'
                            ) ?></span>
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
                    </div>

                    <!-- Receipt image -->
                    <?php if (!empty($row['receipt_image'])): ?>
                        <div class="receipt-wrapper">
                            <p class="receipt-label">
                                <i class="fas fa-receipt" aria-hidden="true"></i>
                                Payment Receipt
                            </p>
                            <a href="uploads/<?= htmlspecialchars($row['receipt_image'], ENT_QUOTES, 'UTF-8') ?>"
                               target="_blank" rel="noopener noreferrer"
                               aria-label="View full receipt image">
                                <img class="receipt-img"
                                     src="uploads/<?= htmlspecialchars($row['receipt_image'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="Payment receipt for booking #<?= $id ?>">
                                <span class="receipt-hint">
                                    <i class="fas fa-magnifying-glass-plus"></i> Click to enlarge
                                </span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="no-receipt">
                            <i class="fas fa-image" aria-hidden="true"></i>
                            No receipt uploaded
                        </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="actions">

                        <?php if ($row['payment_status'] === 'Pending Verification'): ?>

                            <form method="POST"
                                  onsubmit="return confirm('Approve payment for booking #<?= $id ?>?')">
                                <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
                                <input type="hidden" name="booking_id"  value="<?= $id ?>">
                                <input type="hidden" name="action"      value="approve">
                                <button type="submit" class="btn approve">
                                    <i class="fas fa-check" aria-hidden="true"></i> Approve
                                </button>
                            </form>

                            <form method="POST"
                                  onsubmit="return confirm('Reject payment for booking #<?= $id ?>? The receipt will be cleared.')">
                                <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
                                <input type="hidden" name="booking_id"  value="<?= $id ?>">
                                <input type="hidden" name="action"      value="reject">
                                <button type="submit" class="btn reject">
                                    <i class="fas fa-times" aria-hidden="true"></i> Reject
                                </button>
                            </form>

                        <?php elseif ($row['payment_status'] === 'Paid'): ?>

                            <span class="status confirmed" style="padding:9px 16px;">
                                <i class="fas fa-circle-check"></i> Payment Verified
                            </span>

                        <?php else: ?>

                            <span class="waiting-payment">
                                <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                                Awaiting customer payment…
                            </span>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endwhile; ?>

            </div>

        <?php else: ?>
            <div class="empty">
                <i class="fas fa-receipt" style="font-size:2.5rem;margin-bottom:14px;opacity:.3"></i>
                <p>No payment records found.</p>
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