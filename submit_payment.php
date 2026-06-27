<?php
session_start();
include "db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$service = $_POST['service'];
$amount = $_POST['amount'];
$reference = $_POST['reference_number'];

$sql = "INSERT INTO payments (user_id, service, amount, reference_number, status)
        VALUES ('$user_id', '$service', '$amount', '$reference', 'pending')";

if (mysqli_query($conn, $sql)) {
    // optional: update booking status here
    header("Location: success.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>