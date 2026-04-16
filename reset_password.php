<?php
require 'db_connection.php';

if(isset($_GET['token'])){
    $token = $_GET['token'];

    // Check token
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM forgot_password WHERE token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 0){
        die("Invalid token.");
    }

    $row = $result->fetch_assoc();
    if(strtotime($row['expires_at']) < time()){
        die("Token expired.");
    }

    $user_id = $row['user_id'];

    // Reset password
    if(isset($_POST['password'])){
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $password, $user_id);
        $stmt->execute();
        echo "Password reset successfully!";
        exit;
    }
}
?>

<form method="POST">
    <input type="password" name="password" placeholder="New password" required>
    <button type="submit">Reset Password</button>
</form>