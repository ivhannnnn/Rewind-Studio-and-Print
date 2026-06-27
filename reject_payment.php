<?php
session_start();
include("db.php");

if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$booking_id    = (int)    ($_POST['booking_id']    ?? 0);
$reject_reason = trim($_POST['reject_reason'] ?? '');

if ($booking_id <= 0) {
    $_SESSION['msg'] = ['type' => 'error', 'text' => 'Invalid booking ID.'];
    header("Location: admin_payments.php");
    exit();
}

// Fetch old receipt path so we can delete the file
$row = $conn->query("SELECT receipt_image FROM bookings WHERE id = $booking_id")->fetch_assoc();
if ($row && !empty($row['receipt_image'])) {
    $filepath = __DIR__ . '/' . $row['receipt_image'];
    if (file_exists($filepath)) {
        @unlink($filepath);
    }
}

$stmt = $conn->prepare("
    UPDATE bookings
    SET payment_status = 'Unpaid',
        receipt_image  = NULL,
        reference_number = NULL,
        reject_reason  = ?
    WHERE id = ?
");
$stmt->bind_param("si", $reject_reason, $booking_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['msg'] = ['type' => 'success', 'text' => "Payment for Booking #$booking_id rejected. Customer can re-submit."];
} else {
    $_SESSION['msg'] = ['type' => 'error', 'text' => "Could not reject — booking not found."];
}

header("Location: admin_payments.php");
exit();
?>