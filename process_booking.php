<?php
session_start();
include("db.php");

// Make sure user is logged in
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

// Check form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $service_name = $_POST['service_name'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $location = $_POST['location'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Prepare insert query
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_name, event_date, event_time, location, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $service_name, $event_date, $event_time, $location, $notes);

    if($stmt->execute()){
        $_SESSION['booking_success'] = "Your booking for '{$service_name}' has been successfully created!";
        header("Location: book.php?service=" . urlencode($service_name));
        exit();
    } else {
        $_SESSION['booking_error'] = "Booking failed: " . $stmt->error;
        header("Location: book.php?service=" . urlencode($service_name));
        exit();
    }
}
?>