<?php
session_start();
include("db.php");

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT
        b.*,
        r.rating,
        r.feedback
    FROM bookings b
    LEFT JOIN reviews r ON b.id = r.booking_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status — Rewind Studio</title>
    <link rel="stylesheet" href="booking_status_v11.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ── Header ── -->
<header>
    <div class="logo">
        <img src="385319258_714193217393851_8500146797645932462_n (1).jpg" alt="Rewind Studio logo">
        <span>Rewind Studio and Prints</span>
    </div>

    <div class="welcome-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>!</h2>
    </div>

    <nav class="nav">
        <a href="dashboard.php"      class="<?= $current === 'dashboard.php'      ? 'active' : '' ?>">Home</a>
        <a href="services.php"       class="<?= $current === 'services.php'       ? 'active' : '' ?>">Services</a>
        <a href="user_messages.php"  class="<?= $current === 'user_messages.php'  ? 'active' : '' ?>">Messages</a>
        <a href="booking_status.php" class="<?= $current === 'booking_status.php' ? 'active' : '' ?>">Booking Status</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </nav>
</header>

<!-- ── Content ── -->
<section class="hero">
<div class="booking-container">

    <h1>Your Bookings</h1>

    <?php if ($result->num_rows === 0): ?>
        <p class="empty">You have no bookings yet.</p>

    <?php else: ?>
        <?php while ($row = $result->fetch_assoc()):
            $status       = strtolower(trim($row['status']         ?? 'unknown'));
            $payment      = strtolower(trim($row['payment_status'] ?? 'unknown'));
            $statusClass  = 'booking-'  . str_replace(' ', '-', $status);
            $paymentClass = 'payment-'  . str_replace(' ', '-', $payment);

            $dateFormatted = !empty($row['created_at'])
                ? date('F j, Y \a\t g:i A', strtotime($row['created_at']))
                : '—';
        ?>

        <div class="booking-card">

            <!-- Details -->
            <div class="booking-info">
                <p><strong>Booking ID:</strong> #<?= (int)$row['id']; ?></p>
                <p><strong>Service:</strong> <?= htmlspecialchars($row['service_name']); ?></p>
                <p><strong>Date:</strong> <?= $dateFormatted; ?></p>
            </div>

            <!-- Badges -->
            <div class="status-wrapper">
                <span class="badge <?= $statusClass; ?>">
                    <i class="fas fa-circle-dot" style="font-size:8px;vertical-align:1px;margin-right:5px;"></i>
                    <?= htmlspecialchars($row['status']); ?>
                </span>
                <span class="badge <?= $paymentClass; ?>">
                    <i class="fas fa-credit-card" style="font-size:8px;vertical-align:1px;margin-right:5px;"></i>
                    <?= htmlspecialchars($row['payment_status']); ?>
                </span>
            </div>

            <!-- Review -->
            <div class="rating-box">
                <?php if (!empty($row['rating'])): ?>
                    <p><strong>Your Rating</strong></p>
                    <div class="stars">
                        <?php
                            $rating = (int)$row['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '⭐' : '☆';
                            }
                        ?>
                    </div>
                    <?php if (!empty($row['feedback'])): ?>
                        <p><strong>Feedback</strong></p>
                        <p><?= htmlspecialchars($row['feedback']); ?></p>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="no-rating">No review submitted yet.</p>
                    <?php if ($status === 'completed'): ?>
                        <a href="rate.php?booking_id=<?= (int)$row['id']; ?>" class="btn-rate">
                            <i class="fas fa-star"></i> Write a Review
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Message admin -->
            <form action="user_chat.php" method="GET" class="msg-form">
                <input type="hidden" name="booking_id" value="<?= (int)$row['id']; ?>">
                <button type="submit" class="btn-message">Message Admin</button>
            </form>

        </div>

        <?php endwhile; ?>
    <?php endif; ?>

</div>
</section>

</body>
</html>