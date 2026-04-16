<?php
session_start();
include("db.php");

$error = "";

// Only process if login form submitted
if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $admin = $result->fetch_assoc();

        if(password_verify($password, $admin['password'])){
            // ✅ Standardized session
            $_SESSION['admin'] = [
                'id' => $admin['id'],
                'full_name' => $admin['full_name'],
                'username' => $admin['username']
            ];

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Username not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Rewind Studio</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<section class="hero">
    <div class="hero-container" style="max-width: 400px; margin:auto;">
        <div class="booking-card" style="padding:40px; text-align:center; backdrop-filter: blur(10px); background: rgba(255,255,255,0.12); border-radius: 20px; border:1px solid rgba(255,255,255,0.2); box-shadow:0 20px 40px rgba(0,0,0,0.4);">
            <h2>Admin Login</h2>

            <?php if($error): ?>
                <p style="color:red;"><?php echo $error; ?></p>
            <?php endif; ?>

            <form method="post">
                <input type="text" name="username" placeholder="Username" required style="padding:10px; width:100%; margin-bottom:15px; border-radius:10px; border:none;">
                <input type="password" name="password" placeholder="Password" required style="padding:10px; width:100%; margin-bottom:15px; border-radius:10px; border:none;">
                <button type="submit" name="login" class="btn approve" style="width:100%; padding:12px; border-radius:20px; background: linear-gradient(45deg, #ff4d6d, #ff758f); color:#fff; font-weight:600;">Login</button>
            </form>

            <p style="margin-top:15px;">
                Don't have an account? <a href="admin_register.php">Register here</a>
            </p>
        </div>
    </div>
</section>
</body>
</html>