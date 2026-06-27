<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$service = $_GET['service'] ?? 'Unknown Service';

// sample pricing (you can replace with DB later)
$prices = [
    "Photography" => 1500,
    "Video Editing" => 2000,
    "Studio Booking" => 1000
];

$amount = $prices[$service] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment - Rewind Studio</title>
    <link rel="stylesheet" href="payment.css">
</head>
<body>

<div class="payment-container">

    <h2>Payment Checkout</h2>

    <div class="summary">
        <p><strong>Service:</strong> <?php echo $service; ?></p>
        <p><strong>Amount:</strong> ₱<?php echo number_format($amount, 2); ?></p>
    </div>

    <div class="instructions">
        <h3>GCash Payment</h3>
        <p>Send payment to:</p>
        <p><strong>0912-345-6789 (Rewind Studio)</strong></p>
    </div>

    <form action="submit_payment.php" method="POST">
        <input type="hidden" name="service" value="<?php echo $service; ?>">
        <input type="hidden" name="amount" value="<?php echo $amount; ?>">

        <label>GCash Reference Number</label>
        <input type="text" name="reference_number" required>

        <button type="submit">Submit Payment</button>
    </form>

</div>

</body>
</html>