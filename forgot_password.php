<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="forgot_style.css">
</head>
<body>

<div class="forgot-container">
    <h2>Forgot Password</h2>

    <?php
    if(isset($_SESSION['message'])){
        echo '<div class="message">'.$_SESSION['message'].'</div>';
        unset($_SESSION['message']);
    }
    ?>

    <form method="POST" action="send_code.php">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
    </form>
</div>

</body>
</html>