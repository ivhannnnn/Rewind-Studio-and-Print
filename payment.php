<?php
session_start();
include "db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$booking_id = (int) ($_GET['booking_id'] ?? 0);

if ($booking_id <= 0) {
    header("Location: booking_status.php");
    exit();
}

// Fetch the booking — must belong to this user and be Approved + Unpaid
$stmt = $conn->prepare("
    SELECT id, service_name, package_price, down_payment, payment_status, status
    FROM   bookings
    WHERE  id = ? AND user_id = ?
");
$stmt->bind_param("ii", $booking_id, $_SESSION['user']['id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header("Location: booking_status.php");
    exit();
}

// Guard: only show payment page if booking is Approved and still Unpaid
if ($booking['status'] !== 'Approved' || strtolower($booking['payment_status']) !== 'unpaid') {
    header("Location: booking_status.php");
    exit();
}

$service      = htmlspecialchars($booking['service_name'], ENT_QUOTES, 'UTF-8');
$package_price = (float) $booking['package_price'];
$down_payment  = (float) $booking['down_payment'];

// Fallback: compute 30 % if down_payment was never saved
if ($down_payment <= 0 && $package_price > 0) {
    $down_payment = $package_price * 0.30;
}

$balance = $package_price - $down_payment;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Down Payment — Rewind Studio</title>
    <link rel="stylesheet" href="paymentv1.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── extras on top of payment.css ── */
        body { font-family: 'Segoe UI', sans-serif; }

        .payment-container {
            width: 460px;
            margin: 60px auto;
            padding: 32px;
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 18px;
        }

        h2 { margin: 0 0 6px; font-size: 1.4rem; }
        .sub { opacity: .65; font-size: .9rem; margin-bottom: 24px; }

        /* Pricing table */
        .price-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .price-table td { padding: 8px 4px; font-size: .95rem; }
        .price-table td:last-child { text-align: right; font-weight: 600; }
        .price-table tr.highlight td { color: #00e5ff; font-size: 1.05rem; }
        .price-table tr.balance  td { opacity: .65; }
        .price-table .divider td { border-top: 1px solid rgba(255,255,255,.15); padding-top: 12px; }

        /* GCash info box */
        .gcash-box {
            background: rgba(0,194,255,.08);
            border: 1px solid rgba(0,194,255,.3);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 22px;
            text-align: center;
        }
        .gcash-box .gcash-label { font-size: .8rem; opacity: .65; margin-bottom: 4px; }
        .gcash-box .gcash-number { font-size: 1.25rem; font-weight: 700; color: #00e5ff; letter-spacing: 1px; }
        .gcash-box .gcash-name   { font-size: .85rem; opacity: .8; }

        /* Amount pill */
        .amount-pill {
            display: inline-block;
            background: rgba(0,194,255,.15);
            border: 1px solid rgba(0,194,255,.35);
            border-radius: 30px;
            padding: 4px 14px;
            font-size: .82rem;
            margin-top: 8px;
            color: #00e5ff;
        }

        /* Upload area */
        .upload-label {
            display: block;
            margin-bottom: 6px;
            font-size: .9rem;
            opacity: .85;
        }
        .upload-box {
            border: 2px dashed rgba(255,255,255,.25);
            border-radius: 12px;
            padding: 22px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s;
            margin-bottom: 18px;
            position: relative;
        }
        .upload-box:hover  { border-color: #00c2ff; }
        .upload-box input  { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
        .upload-box i      { font-size: 2rem; opacity: .5; margin-bottom: 8px; display: block; }
        .upload-box span   { font-size: .85rem; opacity: .6; }
        #preview-wrap      { margin-bottom: 18px; display: none; }
        #preview-wrap img  { width: 100%; border-radius: 10px; border: 1px solid rgba(255,255,255,.15); }

        /* Ref input */
        .field-label { display: block; margin-bottom: 6px; font-size: .9rem; opacity: .85; }
        input[type=text] {
            width: 100%; padding: 12px 14px; border-radius: 10px;
            background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.2);
            color: #fff; font-size: .95rem; margin-bottom: 18px;
            outline: none; transition: border-color .2s;
        }
        input[type=text]:focus { border-color: #00c2ff; }

        /* Submit */
        .btn-submit {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #00c2ff, #0070cc);
            border: none; border-radius: 12px; color: #fff; font-size: 1rem;
            font-weight: 700; cursor: pointer; transition: opacity .2s;
        }
        .btn-submit:hover { opacity: .88; }

        @media (max-width: 500px) { .payment-container { width: 94%; margin: 30px auto; } }
    </style>
</head>
<body>

<div class="payment-container">

    <h2><i class="fas fa-credit-card" style="color:#00c2ff;margin-right:8px;"></i>Down Payment</h2>
    <p class="sub">Booking #<?= $booking_id ?> — <?= $service ?></p>

    <!-- Pricing breakdown -->
    <table class="price-table">
        <tr>
            <td>Package Price</td>
            <td>₱<?= number_format($package_price, 2) ?></td>
        </tr>
        <tr class="divider highlight">
            <td>Down Payment Required (30%)</td>
            <td>₱<?= number_format($down_payment, 2) ?></td>
        </tr>
        <tr class="balance">
            <td>Remaining Balance (on event day)</td>
            <td>₱<?= number_format($balance, 2) ?></td>
        </tr>
    </table>

    <!-- GCash details -->
    <div class="gcash-box">
        <div class="gcash-label">Send via GCash to</div>
        <div class="gcash-number">0912-345-6789</div>
        <div class="gcash-name">Rewind Studio and Prints</div>
        <div class="amount-pill">Exact amount: ₱<?= number_format($down_payment, 2) ?></div>
    </div>

    <!-- Upload form -->
    <form action="submit_payment.php" method="POST" enctype="multipart/form-data"
          onsubmit="return validateForm()">
        <input type="hidden" name="booking_id"   value="<?= $booking_id ?>">
        <input type="hidden" name="amount"        value="<?= $down_payment ?>">
        <input type="hidden" name="service"       value="<?= $service ?>">

        <!-- Receipt upload -->
        <label class="upload-label">
            <i class="fas fa-image" style="margin-right:5px;"></i>GCash Receipt Screenshot
        </label>
        <div class="upload-box" id="uploadBox">
            <input type="file" name="receipt_image" id="receiptInput"
                   accept="image/jpeg,image/png,image/webp,image/gif"
                   onchange="previewImage(this)" required>
            <i class="fas fa-cloud-upload-alt"></i>
            <span id="uploadText">Click or drag your receipt here<br>(JPG, PNG, WEBP)</span>
        </div>

        <!-- Image preview -->
        <div id="preview-wrap">
            <img id="previewImg" src="" alt="Receipt preview">
        </div>

        <!-- Reference number -->
        <label class="field-label" for="refInput">
            <i class="fas fa-hashtag" style="margin-right:4px;"></i>GCash Reference Number
        </label>
        <input type="text" id="refInput" name="reference_number"
               placeholder="e.g. 1234567890" maxlength="20" required>

        <button type="submit" class="btn-submit">
            <i class="fas fa-paper-plane" style="margin-right:6px;"></i>Submit Down Payment
        </button>
    </form>

</div>

<script>
function previewImage(input) {
    const wrap = document.getElementById('preview-wrap');
    const img  = document.getElementById('previewImg');
    const text = document.getElementById('uploadText');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            wrap.style.display = 'block';
            text.textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function validateForm() {
    const ref = document.getElementById('refInput').value.trim();
    if (!/^\d{10,13}$/.test(ref)) {
        alert('Please enter a valid GCash reference number (10–13 digits).');
        return false;
    }
    return true;
}
</script>

</body>
</html>