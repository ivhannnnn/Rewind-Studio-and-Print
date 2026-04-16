<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

include("db.php");

$booking_id = $_GET['booking_id'] ?? null;
$user_id = $_SESSION['user']['id'];

$success = "";
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $message = trim($_POST['message']);
    if(!empty($message)){
        $stmt = $conn->prepare("INSERT INTO messages (user_id, booking_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $booking_id, $message);
        $stmt->execute();
        $success = "Message sent successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Message Admin - Rewind Studio</title>
    <link rel="stylesheet" href="booking_status.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Override/additional styles to match your design */
        .message-section {
            max-width: 600px;
            margin: 50px auto;
        }

        .message-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .message-card h2 {
            margin-bottom: 15px;
            color: #222;
            font-size: 1.5rem;
            text-align: center;
        }

        .message-card textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            resize: vertical;
            margin-bottom: 15px;
        }

        .message-card button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #ff4c4c;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .message-card button:hover {
            background: #e03e3e;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #c3e6cb;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }

        .back-link {
            display: block;
            margin-top: 15px;
            text-align: center;
            text-decoration: none;
            color: #ff4c4c;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>

<section class="message-section">
    <div class="message-card">
        <h2>Message Admin</h2>

        <?php if($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <textarea name="message" rows="6" placeholder="Type your message here..." required></textarea>
            <button type="submit"><i class="fas fa-paper-plane"></i> Send Message</button>
        </form>

        <a class="back-link" href="booking_status.php"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
    </div>
</section>

</body>
</html>