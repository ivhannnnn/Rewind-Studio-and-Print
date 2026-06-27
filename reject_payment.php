<?php
include("db.php");

$booking_id = $_POST['booking_id'];

mysqli_query($conn, "
    UPDATE bookings 
    SET 
        payment_status = 'Unpaid',
        receipt_image = NULL
    WHERE id = '$booking_id'
");

header("Location: admin_payments.php");
exit();
?>