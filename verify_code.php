<?php
session_start();

$error = "";

if(isset($_POST['verify'])){

    $code = $_POST['code'];

    if($code == $_SESSION['reset_code']){
        header("Location: reset_password.php");
        exit();
    }else{
        $error = "Invalid verification code!";
    }

}
?>

<!DOCTYPE html>
<html>
<head>
<title>Verify Code | Rewind Studio</title>
<link rel="stylesheet" href="login_v4.css">
</head>

<body>

<div class="card-container">

<div class="front">

<h1>Verify Code</h1>
<p class="subtitle">Enter the code sent to your email</p>

<?php if($error): ?>
<div class="glass-message"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">

<div class="form-group">
<label>Verification Code</label>
<input type="text" name="code" placeholder="Enter 6-digit code" required>
</div>

<button type="submit" name="verify" class="glass-btn">Verify Code</button>

</form>

<div class="home-btn-container">
<a href="login.php" class="home-btn">Back to Login</a>
</div>

</div>

</div>

</body>
</html>