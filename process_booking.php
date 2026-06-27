<?php
session_start();
include("db.php");

// ================= CHECK LOGIN =================

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

// ================= SERVICE PRICES =================

$prices = [

    "Wedding" => 15000,

    "Debut" => 8000,

    "Graduation" => 3000,

    "Pre-Birthday" => 3500,

    "Prenup" => 10000

];

// ================= PROCESS FORM =================

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $service_name = trim($_POST['service_name']);
    $event_date   = $_POST['event_date'];
    $event_time   = $_POST['event_time'];
    $location     = trim($_POST['location']);
    $notes        = trim($_POST['notes']);

    // ================= VALIDATE DATE =================

    if ($event_date < date("Y-m-d")) {

        $_SESSION['booking_error'] =
            "❌ You cannot book a past date.";

        header("Location: book.php?service=" . urlencode($service_name));
        exit();

    }

    // ================= PRICE =================

    $package_price = $prices[$service_name] ?? 0;

    $down_payment = $package_price * 0.30;

    // ================= CHECK EXISTING BOOKING =================

    $check = $conn->prepare("
        SELECT id
        FROM bookings
        WHERE event_date = ?
        AND status != 'Cancelled'
    ");

    $check->bind_param("s", $event_date);

    $check->execute();

    $check->store_result();

    if ($check->num_rows > 0) {

        $_SESSION['booking_error'] =
        "❌ The selected date is already booked. Please choose another date.";

        header("Location: book.php?service=" . urlencode($service_name));
        exit();

    }

    // ================= INSERT BOOKING =================

    $stmt = $conn->prepare("

        INSERT INTO bookings
        (

            user_id,

            service_name,

            package_price,

            down_payment,

            event_date,

            event_time,

            location,

            notes,

            status,

            payment_status

        )

        VALUES

        (

            ?,

            ?,

            ?,

            ?,

            ?,

            ?,

            ?,

            ?,

            'Pending',

            'Unpaid'

        )

    ");

    $stmt->bind_param(

        "isddssss",

        $user_id,

        $service_name,

        $package_price,

        $down_payment,

        $event_date,

        $event_time,

        $location,

        $notes

    );

    if ($stmt->execute()) {

        $_SESSION['booking_success'] =

        "🎉 Booking request submitted successfully!

        Please wait for the administrator to approve your booking.

        Once approved, you can pay your required down payment.";

    } else {

        $_SESSION['booking_error'] =

        "Booking failed: " . $stmt->error;

    }

    header("Location: book.php?service=" . urlencode($service_name));

    exit();

}
?>