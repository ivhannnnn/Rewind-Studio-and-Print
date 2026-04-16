<?php
session_start();
require 'db.php'; // your database connection

if(isset($_POST['email'])){
    $email = $_POST['email'];

    // 1️⃣ Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 0){
        die("Email not found.");
    }

    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // 2️⃣ Generate token
    $token = bin2hex(random_bytes(16));
    $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // 3️⃣ Store token in DB
    $stmt = $conn->prepare("INSERT INTO forgot_password (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $token, $expires);
    $stmt->execute();

    // 4️⃣ Generate reset link
    $resetUrl = "http://localhost/Rewind_Studio_and_print/reset_password.php?token=$token";

    // 5️⃣ Send email using PHP mail()
    $to = $email;
    $subject = "Reset Password";
    $message = "Click the link to reset your password: $resetUrl";
    $headers = "From: your_email@gmail.com";

    // <- THIS IS WHERE YOUR MAIL FUNCTION GOES
    if(mail($to, $subject, $message, $headers)){
        echo "Reset email sent!";
    } else {
        echo "Email failed.";
    }
}
?>