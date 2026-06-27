<?php
session_start();
include("db.php");

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* ── Unread messages badge ─────────────────────────────────────────────── */
$unread_messages = (int) $conn->query(
    "SELECT COUNT(*) as c FROM messages WHERE status='unread'"
)->fetch_assoc()['c'];

$admin_name = htmlspecialchars($_SESSION['admin']['full_name'], ENT_QUOTES, 'UTF-8');

/* ── Fetch bookings awaiting payment verification ──────────────────────── */
$pending = $conn->query("
    SELECT b.*, u.full_name, u.email
    FROM   bookings b
    JOIN   users    u ON b.user_id = u.id
    WHERE  b.payment_status IN ('Pending Verification', 'Unpaid', 'Paid')
    ORDER  BY
        CASE b.payment_status
            WHEN 'Pending Verification' THEN 1
            WHEN 'Unpaid'              THEN 2
            WHEN 'Paid'               THEN 3
        END,
        b.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments — Rewind Admin</title>
    <link rel="stylesheet" href="admin_dashboardv4.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Receipt lightbox ─────────────────────────────────────────── */
        .lightbox {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.85); z-index: 9999;
            justify-content: center; align-items: center;
        }
        .lightbox.open { display: flex; }
        .lightbox img  {
            max-width: 90vw; max-height: 88vh;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,.6);
        }
        .lightbox-close {
            position: absolute; top: 18px; right: 22px;
            background: none; border: none; color: #fff;
            font-size: 2rem; cursor: pointer;
        }

        /* ── Topbar ───────────────────────────────────────────────────── */
        .topbar {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 24px;
        }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .topbar-eyebrow { font-size: .75rem; opacity: .55; text-transform: uppercase; margin: 0; }
        .topbar-title   { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .admin-chip { display: flex; align-items: center; gap: 10px; }
        .admin-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,.15);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }

        /* ── Flash alert ─────────────────────────────────────────────── */
        .alert {
            padding: 13px 18px; border-radius: 12px;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: rgba(74,222,128,.15); border: 1px solid rgba(74,222,128,.3); color: #4ade80; }
        .alert-error   { background: rgba(239,68,68,.15);  border: 1px solid rgba(239,68,68,.3);  color: #f87171; }

        /* ── Filter tabs ─────────────────────────────────────────────── */
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 22px; flex-wrap: wrap; }
        .tab-btn {
            padding: 8px 18px; border-radius: 30px;
            background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.15);
            color: #fff; cursor: pointer; font-size: .85rem; transition: .2s;
        }
        .tab-btn.active,
        .tab-btn:hover { background: rgba(0,194,255,.2); border-color: rgba(0,194,255,.5); }

        /* ── Payment card ─────────────────────────────────────────────── */
        .payment-card {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.13);
            backdrop-filter: blur(14px);
            border-radius: 18px;
            padding: 22px;
            transition: transform .25s;
            break-inside: avoid;
            margin-bottom: 18px;
        }
        .payment-card:hover { transform: translateY(-4px); }

        .payment-card h3 { margin: 0 0 4px; font-size: 1rem; }
        .payment-card .meta { font-size: .82rem; opacity: .6; margin: 0 0 14px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; margin-bottom: 14px; }
        .info-item label { display: block; font-size: .72rem; opacity: .5; text-transform: uppercase; }
        .info-item span  { font-size: .92rem; }

        /* Receipt thumbnail */
        .receipt-thumb {
            width: 100%; border-radius: 12px; object-fit: cover; max-height: 200px;
            border: 1px solid rgba(255,255,255,.12); cursor: zoom-in;
            margin-bottom: 14px;
        }
        .no-receipt {
            background: rgba(255,255,255,.05);
            border: 1px dashed rgba(255,255,255,.2);
            border-radius: 12px; padding: 22px;
            text-align: center; opacity: .45; font-size: .85rem;
            margin-bottom: 14px;
        }

        /* Payment status badge */
        .pay-badge {
            display: inline-block; padding: 4px 12px;
            border-radius: 20px; font-size: .78rem; font-weight: 600;
            margin-bottom: 14px;
        }
        .pay-badge.pending-verification { background: rgba(251,191,36,.15); color: #fbbf24; border: 1px solid rgba(251,191,36,.35); }
        .pay-badge.unpaid  { background: rgba(239,68,68,.12); color: #f87171; border: 1px solid rgba(239,68,68,.3); }
        .pay-badge.paid    { background: rgba(74,222,128,.12); color: #4ade80; border: 1px solid rgba(74,222,128,.3); }

        /* Action buttons */
        .card-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { flex: 1; padding: 11px 10px; border: none; border-radius: 10px; cursor: pointer; font-size: .88rem; font-weight: 600; color: #fff; transition: opacity .2s, transform .2s; }
        .btn:hover { opacity: .86; transform: translateY(-1px); }
        .btn-approve { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .btn-reject  { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .btn-view    { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2); }

        /* Grid layout */
        .cards-grid {
            columns: 2 400px;
            column-gap: 18px;
        }

        /* Reject modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 9000; justify-content: center; align-items: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: #1a1a2e; border: 1px solid rgba(255,255,255,.15); border-radius: 18px; padding: 28px; width: 400px; max-width: 94vw; }
        .modal-box h3 { margin: 0 0 14px; }
        .modal-box textarea { width: 100%; padding: 10px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.2); border-radius: 10px; color: #fff; font-size: .9rem; resize: vertical; min-height: 80px; margin-bottom: 14px; }
        .modal-actions { display: flex; gap: 10px; }

        @media (max-width: 600px) { .cards-grid { columns: 1; } }
    </style>
</head>
<body>

<div class="admin-layout">

    <!-- ═══ SIDEBAR ══════════════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="385319258_714193217393851_8500146797645932462_n (1).jpg" alt="Logo">
            <h2>Rewind Studio</h2>
            <span>Admin Panel</span>
        </div>
        <nav>
            <a href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="admin_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
            <a href="admin_payments.php" class="active"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="admin_messages.php">
                <i class="fas fa-envelope"></i> Messages
                <?php if ($unread_messages > 0): ?>
                    <span class="badge"><?= $unread_messages ?></span>
                <?php endif; ?>
            </a>
            <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
            <a href="logout.php" class="nav-logout"><i class="fas fa-right-from-bracket"></i> Log Out</a>
        </nav>
    </aside>

    <!-- ═══ MAIN ═════════════════════════════════════════════════════════ -->
    <main class="main">

        <div class="topbar">
            <div class="topbar-left">
                <div>
                    <p class="topbar-eyebrow">Verify</p>
                    <h1 class="topbar-title">Payments</h1>
                </div>
            </div>
            <div class="admin-chip">
                <div class="admin-avatar"><?= strtoupper(substr($_SESSION['admin']['full_name'], 0, 1)) ?></div>
                <span><?= $admin_name ?></span>
            </div>
        </div>

        <!-- Flash -->
        <?php if (isset($_SESSION['msg'])): ?>
            <?php $msg = $_SESSION['msg']; unset($_SESSION['msg']); ?>
            <div class="alert alert-<?= htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas <?= $msg['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                <?= htmlspecialchars($msg['text'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Filter tabs -->
        <div class="filter-tabs">
            <button class="tab-btn active" onclick="filterCards('all', this)">All</button>
            <button class="tab-btn" onclick="filterCards('pending-verification', this)">
                <i class="fas fa-hourglass-half"></i> Pending Review
            </button>
            <button class="tab-btn" onclick="filterCards('unpaid', this)">
                <i class="fas fa-clock"></i> Unpaid
            </button>
            <button class="tab-btn" onclick="filterCards('paid', this)">
                <i class="fas fa-circle-check"></i> Paid
            </button>
        </div>

        <?php if ($pending->num_rows > 0): ?>
        <div class="cards-grid" id="cardsGrid">

        <?php while ($row = $pending->fetch_assoc()):
            $id       = (int) $row['id'];
            $pstatus  = strtolower(trim($row['payment_status']));
            $pclass   = str_replace(' ', '-', $pstatus);
            $downpay  = (float) $row['down_payment'];
            $package  = (float) $row['package_price'];
        ?>

            <div class="payment-card" data-status="<?= htmlspecialchars($pclass, ENT_QUOTES) ?>">

                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h3><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="meta"><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <span class="pay-badge <?= $pclass ?>">
                        <?= htmlspecialchars($row['payment_status'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <!-- Info grid -->
                <div class="info-grid">
                    <div class="info-item">
                        <label>Booking #</label>
                        <span>#<?= $id ?></span>
                    </div>
                    <div class="info-item">
                        <label>Service</label>
                        <span><?= htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="info-item">
                        <label>Package Price</label>
                        <span>₱<?= number_format($package, 2) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Down Payment (30%)</label>
                        <span style="color:#00e5ff;font-weight:700;">₱<?= number_format($downpay, 2) ?></span>
                    </div>
                    <?php if (!empty($row['reference_number'])): ?>
                    <div class="info-item">
                        <label>GCash Reference</label>
                        <span><?= htmlspecialchars($row['reference_number'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <label>Event Date</label>
                        <span><?= htmlspecialchars(date('M j, Y', strtotime($row['event_date'])), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <!-- Receipt image -->
                <?php if (!empty($row['receipt_image'])): ?>
                    <img class="receipt-thumb"
                         src="<?= htmlspecialchars($row['receipt_image'], ENT_QUOTES, 'UTF-8') ?>"
                         alt="GCash Receipt"
                         onclick="openLightbox(this.src)">
                <?php else: ?>
                    <div class="no-receipt">
                        <i class="fas fa-image"></i><br>No receipt uploaded yet
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="card-actions">
                    <?php if ($pstatus === 'pending verification'): ?>

                        <!-- Approve -->
                        <form method="POST" action="approve_payment.php" style="flex:1;">
                            <input type="hidden" name="booking_id" value="<?= $id ?>">
                            <button type="submit" class="btn btn-approve"
                                    onclick="return confirm('Approve payment for Booking #<?= $id ?>?')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>

                        <!-- Reject (opens modal) -->
                        <button class="btn btn-reject"
                                onclick="openRejectModal(<?= $id ?>)">
                            <i class="fas fa-times"></i> Reject
                        </button>

                    <?php elseif ($pstatus === 'paid'): ?>
                        <span style="color:#4ade80;font-size:.9rem;">
                            <i class="fas fa-circle-check"></i> Payment verified
                        </span>
                    <?php else: ?>
                        <span style="opacity:.5;font-size:.9rem;">
                            <i class="fas fa-hourglass-half"></i> Waiting for customer payment
                        </span>
                    <?php endif; ?>
                </div>

            </div>

        <?php endwhile; ?>
        </div>

        <?php else: ?>
            <div class="empty">
                <i class="fas fa-receipt" style="font-size:2.5rem;opacity:.25;display:block;margin-bottom:12px;"></i>
                <p>No payment records found.</p>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- ═══ Reject modal ══════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal-box">
        <h3><i class="fas fa-times-circle" style="color:#ef4444;margin-right:8px;"></i>Reject Payment</h3>
        <p style="opacity:.7;font-size:.9rem;margin-bottom:14px;">
            The customer will be notified and can re-submit their receipt.
        </p>
        <form method="POST" action="reject_payment.php">
            <input type="hidden" name="booking_id" id="rejectBookingId">
            <textarea name="reject_reason" placeholder="Reason for rejection (optional)…"></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-view" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-reject">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ Lightbox ══════════════════════════════════════════════════════════ -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <img id="lightboxImg" src="" alt="Receipt full view">
</div>

<script>
/* ── Filter cards ──────────────────────────────────────────────────────── */
function filterCards(status, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.payment-card').forEach(card => {
        card.style.display =
            (status === 'all' || card.dataset.status === status) ? '' : 'none';
    });
}

/* ── Reject modal ──────────────────────────────────────────────────────── */
function openRejectModal(id) {
    document.getElementById('rejectBookingId').value = id;
    document.getElementById('rejectModal').classList.add('open');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('open');
}

/* ── Lightbox ──────────────────────────────────────────────────────────── */
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
}
</script>

</body>
</html>