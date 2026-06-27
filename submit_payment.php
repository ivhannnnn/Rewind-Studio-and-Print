<?php
session_start();
include "db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id    = (int) $_SESSION['user']['id'];
$booking_id = (int) ($_POST['booking_id'] ?? 0);
$amount     = (float) ($_POST['amount'] ?? 0);
$service    = trim($_POST['service'] ?? '');
$reference  = trim($_POST['reference_number'] ?? '');

// ── Validate inputs ─────────────────────────────────────────────────────────
if ($booking_id <= 0 || $amount <= 0 || $reference === '') {
    die("Invalid submission. Please go back and try again.");
}

if (!preg_match('/^\d{10,13}$/', $reference)) {
    die("Invalid GCash reference number. Please go back and enter a valid one.");
}

// ── Verify the booking belongs to this user and is Approved ─────────────────
$stmt = $conn->prepare("
    SELECT id FROM bookings
    WHERE id = ? AND user_id = ? AND LOWER(status) = 'approved'
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    die("Booking not found or not yet approved by admin.");
}

// ── Handle receipt upload ────────────────────────────────────────────────────
$upload_dir = __DIR__ . '/uploads/receipts/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

if (empty($_FILES['receipt_image']['tmp_name'])) {
    die("Please upload your GCash receipt screenshot.");
}

$file    = $_FILES['receipt_image'];
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo   = new finfo(FILEINFO_MIME_TYPE);
$mime    = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowed, true)) {
    die("Invalid file type. Only JPG, PNG, WEBP, or GIF are allowed.");
}

if ($file['size'] > 5 * 1024 * 1024) {
    die("File too large. Maximum size is 5 MB.");
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'receipt_' . $booking_id . '_' . time() . '.' . strtolower($ext);
$dest     = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    die("Failed to save receipt. Please try again.");
}

// ── Update booking — use EXACT casing that approve_payment.php checks for ───
$receipt_path = 'uploads/receipts/' . $filename;

$upd = $conn->prepare("
    UPDATE bookings
    SET payment_status   = 'Pending Verification',
        receipt_image    = ?,
        reference_number = ?
    WHERE id = ? AND user_id = ?
");
$upd->bind_param("ssii", $receipt_path, $reference, $booking_id, $user_id);

if ($upd->execute()) {
    header("Location: success.php?booking_id=" . $booking_id);
    exit();
} else {
    @unlink($dest);
    die("Database error: " . $conn->error);
}
?>