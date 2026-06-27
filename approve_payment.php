<?php
session_start();
include("db.php");

if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$booking_id = (int) ($_POST['booking_id'] ?? 0);

if ($booking_id <= 0) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid booking ID.'];
    header("Location: admin_payments.php");
    exit();
}

// Check what the current payment_status actually is
$check   = $conn->query("SELECT payment_status FROM bookings WHERE id = $booking_id");
$current = $check->fetch_assoc();

if (!$current) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => "Booking #$booking_id not found."];
    header("Location: admin_payments.php");
    exit();
}

// Use LOWER + TRIM so case/whitespace mismatches don't block approval
$stmt = $conn->prepare("
    UPDATE bookings
    SET payment_status = 'Paid',
        status         = 'Confirmed',
        approved_at    = NOW()
    WHERE id = ?
    AND LOWER(TRIM(payment_status)) = 'pending verification'
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['msg'] = ['type' => 'success', 'text' => "Payment for Booking #$booking_id approved! Booking is now Confirmed."];
} else {
    $_SESSION['msg'] = [
        'type' => 'error',
        'text' => "Could not approve Booking #$booking_id. Current payment status is: \"" . htmlspecialchars($current['payment_status']) . "\". It must be \"Pending Verification\"."
    ];
}

header("Location: admin_payments.php");
exit();
?>