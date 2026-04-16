<?php
session_start();
include("db.php");

$success = "";
$error = "";

if(isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check password match
    if($password !== $confirm_password){
        $error = "Passwords do not match!";
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $error = "Username already exists!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new admin
            $stmt_insert = $conn->prepare("INSERT INTO admins (full_name, username, password) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $full_name, $username, $hashed_password);

            if($stmt_insert->execute()){
                $success = "Admin registered successfully!";
            } else {
                $error = "Registration failed. Try again!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Register - Rewind Studio</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<section class="hero">
    <div class="hero-container" style="max-width: 400px; margin:auto;">
        <div class="booking-card" style="padding:40px; text-align:center;">
            <h2>Admin Registration</h2>

            <?php if($success): ?>
                <p style="color:green;"><?php echo $success; ?></p>
            <?php endif; ?>

            <?php if($error): ?>
                <p style="color:red;"><?php echo $error; ?></p>
            <?php endif; ?>

            <form method="post">
                <input type="text" name="full_name" placeholder="Full Name" required>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" name="register" class="btn approve" style="width:100%; margin-top:15px;">Register</button>
            </form>

            <p style="margin-top:15px;">
                Already have an account? <a href="admin_login.php">Login here</a>
            </p>
        </div>
    </div>
</section>
</body>
</html>