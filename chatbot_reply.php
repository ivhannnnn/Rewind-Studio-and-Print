<?php
/**
 * chatbot_reply.php
 * Called via AJAX from user_chat.js when admin is offline.
 * Calls Anthropic API, stores the reply in message_replies,
 * and returns JSON for the frontend to render immediately.
 */
session_start();
include("db.php");

header('Content-Type: application/json');

/* ── Auth ─────────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

/* ── Input ────────────────────────────────────────────────────────────── */
$input      = json_decode(file_get_contents('php://input'), true);
$user_msg   = trim($input['message']       ?? '');
$booking_id = (int) ($input['booking_id'] ?? 0);
$message_id = (int) ($input['message_id'] ?? 0);  // the just-inserted message row id
$user_id    = (int) $_SESSION['user']['id'];

if (!$user_msg || !$booking_id || !$message_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

/* ── Verify booking ownership ─────────────────────────────────────────── */
$own = $conn->prepare("
    SELECT service_name, status, payment_status, event_date, package_price, down_payment
    FROM   bookings WHERE id=? AND user_id=? LIMIT 1
");
$own->bind_param("ii", $booking_id, $user_id);
$own->execute();
$booking = $own->get_result()->fetch_assoc();

if (!$booking) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

/* ── Fetch recent conversation history (last 10 exchanges) ───────────── */
$history = [];

$hist_stmt = $conn->prepare("
    SELECT message AS content, 'user' AS role, created_at
    FROM   messages
    WHERE  user_id=? AND booking_id=?
    ORDER  BY created_at DESC
    LIMIT  10
");
$hist_stmt->bind_param("ii", $user_id, $booking_id);
$hist_stmt->execute();
$user_hist = array_reverse($hist_stmt->get_result()->fetch_all(MYSQLI_ASSOC));

/* Also fetch bot replies */
if (!empty($user_hist)) {
    $ids          = array_column(
        $conn->prepare("SELECT id FROM messages WHERE user_id=? AND booking_id=?") ? [] : [],
        'id'
    );
    /* Simpler: fetch all reply history for this booking's messages */
    $rep_hist = $conn->query("
        SELECT mr.reply AS content, 'assistant' AS role, mr.created_at
        FROM   message_replies mr
        JOIN   messages m ON mr.message_id = m.id
        WHERE  m.user_id=$user_id AND m.booking_id=$booking_id
        ORDER  BY mr.created_at DESC
        LIMIT  10
    ");
    $bot_hist = $rep_hist ? array_reverse($rep_hist->fetch_all(MYSQLI_ASSOC)) : [];

    /* Interleave by timestamp */
    $combined = array_merge($user_hist, $bot_hist);
    usort($combined, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));

    foreach ($combined as $h) {
        $history[] = [
            'role'    => $h['role'],
            'content' => $h['content'],
        ];
    }
}

/* ── Build system prompt with booking context ─────────────────────────── */
$event_date = $booking['event_date']
    ? date('F j, Y', strtotime($booking['event_date']))
    : 'not yet set';

$system_prompt = <<<PROMPT
You are the AI assistant for Rewind Studio & Prints, a photography and printing studio in the Philippines. You work alongside the admin team — you reply instantly to every message as a first responder, and the admin may follow up with a personal reply afterward.

You are helping {$_SESSION['user']['full_name']} who has an active booking.

BOOKING DETAILS:
- Service: {$booking['service_name']}
- Booking Status: {$booking['status']}
- Payment Status: {$booking['payment_status']}
- Event Date: {$event_date}
- Package Price: ₱{$booking['package_price']}
- Down Payment Required: ₱{$booking['down_payment']}

BOOKING WORKFLOW (explain when relevant):
Pending → Admin Approves → Customer uploads GCash/bank receipt → Admin verifies payment → Confirmed → Event happens → Completed

YOUR ROLE:
- Give instant, helpful answers about this booking — status, payment steps, event date, what to bring, what to expect
- Guide payment: once Approved, the customer goes to their booking page and uploads their receipt
- For things requiring a human decision (reschedule, refund, custom add-ons, complaints), acknowledge the concern warmly, tell them the admin will personally follow up, and assure them their message is noted
- Be conversational and concise — 1 to 3 short paragraphs max
- Never fabricate prices, dates, or policies not given above
- You are an AI — do not claim to be human, but also don't keep reminding them you're a bot unless asked
- Do NOT prefix replies with "AI Assistant:" — just reply naturally

TONE: Warm, professional, and Filipino-friendly. Light use of "po" is natural if it fits.
PROMPT;

/* ── Call Anthropic API ───────────────────────────────────────────────── */
$api_key = 'YOUR_ANTHROPIC_API_KEY'; // Replace with your key or load from config

$payload = [
    'model'      => 'claude-haiku-4-5-20251001', // fast + cheap for chat
    'max_tokens' => 400,
    'system'     => $system_prompt,
    'messages'   => $history,
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 20,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $http_code !== 200) {
    /* Graceful fallback — don't expose API errors to user */
    $bot_reply = "Thank you for your message! Our team is currently offline but will get back to you as soon as possible. Your booking details have been noted and you'll receive a response shortly.";
} else {
    $data      = json_decode($response, true);
    $bot_reply = trim($data['content'][0]['text'] ?? '');
    if (!$bot_reply) {
        $bot_reply = "Thanks for reaching out! Our admin team will follow up with you soon regarding your booking.";
    }
}

/* ── Store bot reply in message_replies ───────────────────────────────── */
$admin_name = 'AI Assistant';
$ins = $conn->prepare("
    INSERT INTO message_replies (message_id, admin_id, admin_name, reply, created_at)
    VALUES (?, 0, ?, ?, NOW())
");
$ins->bind_param("iss", $message_id, $admin_name, $bot_reply);
$ins->execute();

/* ── Return reply to frontend ─────────────────────────────────────────── */
echo json_encode([
    'success' => true,
    'reply'   => $bot_reply,
    'time'    => date('g:i A'),
]);
exit();