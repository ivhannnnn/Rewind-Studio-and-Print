<?php
/**
 * save_message.php
 * Saves a user chat message and returns its ID (needed for the bot reply FK).
 * Called via fetch() from user_chat.js.
 */
session_start();
include("db.php");

header('Content-Type: application/json');

/* ── Auth ─────────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

/* ── Input ────────────────────────────────────────────────────────────── */
$input      = json_decode(file_get_contents('php://input'), true);
$csrf       = $input['csrf_token'] ?? '';
$booking_id = (int) ($input['booking_id'] ?? 0);
$message    = trim($input['message'] ?? '');
$user_id    = (int) $_SESSION['user']['id'];

/* ── CSRF ─────────────────────────────────────────────────────────────── */
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit();
}

/* ── Validate ─────────────────────────────────────────────────────────── */
if ($booking_id <= 0 || strlen($message) < 1 || strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

/* ── Verify booking ownership ─────────────────────────────────────────── */
$own = $conn->prepare("SELECT id FROM bookings WHERE id=? AND user_id=? LIMIT 1");
$own->bind_param("ii", $booking_id, $user_id);
$own->execute();
if ($own->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

/* ── Insert message ───────────────────────────────────────────────────── */
$stmt = $conn->prepare("
    INSERT INTO messages (user_id, booking_id, message, sender, status, created_at)
    VALUES (?, ?, ?, 'user', 'unread', NOW())
");
$stmt->bind_param("iis", $user_id, $booking_id, $message);
$stmt->execute();

$message_id = (int) $conn->insert_id;

/* Rotate CSRF token */
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo json_encode([
    'success'    => true,
    'message_id' => $message_id,
    'new_csrf'   => htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'),
]);