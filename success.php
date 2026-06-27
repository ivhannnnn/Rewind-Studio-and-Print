<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Submitted — Rewind Studio</title>
    <link rel="stylesheet" href="payment.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .success-box {
            text-align: center;
            padding: 48px 36px;
        }
        .success-icon {
            font-size: 3.5rem;
            color: #4ade80;
            margin-bottom: 18px;
            display: block;
        }
        .success-box h2 { font-size: 1.6rem; margin: 0 0 10px; }
        .success-box p  { opacity: .75; margin: 6px 0; }

        .booking-ref {
            display: inline-block;
            margin: 18px 0 26px;
            background: rgba(74,222,128,.12);
            border: 1px solid rgba(74,222,128,.3);
            border-radius: 30px;
            padding: 6px 20px;
            font-size: .9rem;
            color: #4ade80;
        }

        .steps {
            text-align: left;
            background: rgba(255,255,255,.06);
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 28px;
        }
        .steps h3 { font-size: .9rem; opacity: .6; margin: 0 0 12px; text-transform: uppercase; letter-spacing: .5px; }
        .step-row  { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; font-size: .9rem; }
        .step-row i { width: 20px; text-align: center; }
        .step-row.done  i { color: #4ade80; }
        .step-row.active i { color: #facc15; }
        .step-row.next  i  { opacity: .35; }

        .btn-home {
            display: inline-block;
            padding: 13px 28px;
            background: linear-gradient(135deg, #00c2ff, #0070cc);
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: .95rem;
            margin-right: 10px;
        }
        .btn-status {
            display: inline-block;
            padding: 13px 28px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-size: .95rem;
        }
    </style>
</head>
<body>

<?php
$booking_id = (int) ($_GET['booking_id'] ?? 0);
?>

<div class="success-box">
    <i class="fas fa-circle-check success-icon"></i>
    <h2>Down Payment Submitted!</h2>
    <p>Your receipt has been uploaded and is now under review.</p>
    <p>We will verify your payment and confirm your booking shortly.</p>

    <?php if ($booking_id > 0): ?>
        <div class="booking-ref">Booking #<?= $booking_id ?></div>
    <?php endif; ?>

    <!-- Progress steps -->
    <div class="steps">
        <h3>What happens next</h3>
        <div class="step-row done">
            <i class="fas fa-check-circle"></i>
            <span>Booking submitted</span>
        </div>
        <div class="step-row done">
            <i class="fas fa-check-circle"></i>
            <span>Admin approved your booking</span>
        </div>
        <div class="step-row done">
            <i class="fas fa-check-circle"></i>
            <span>Down payment receipt uploaded</span>
        </div>
        <div class="step-row active">
            <i class="fas fa-hourglass-half"></i>
            <span>Admin verifies your payment — <em>you are here</em></span>
        </div>
        <div class="step-row next">
            <i class="fas fa-circle-check"></i>
            <span>Booking confirmed!</span>
        </div>
    </div>

    <a href="dashboard.php" class="btn-home">
        <i class="fas fa-house" style="margin-right:6px;"></i>Go to Dashboard
    </a>
    <a href="booking_status.php" class="btn-status">
        <i class="fas fa-list" style="margin-right:6px;"></i>My Bookings
    </a>
</div>

</body>
</html>