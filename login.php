<?php
session_start();

// Separate messages
$login_error = $_SESSION['login_error'] ?? "";
$signup_error = $_SESSION['signup_error'] ?? "";
$success = $_SESSION['signup_success'] ?? $_SESSION['logout_success'] ?? "";

// Clear session messages
unset($_SESSION['login_error'], $_SESSION['signup_error'], $_SESSION['signup_success'], $_SESSION['logout_success']);

// Flip only if signup error
$flip = !empty($signup_error);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rewind Studio | Login</title>

<link rel="stylesheet" href="login_v6.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>

<div class="card-container">
<div class="card <?php echo $flip ? 'flip' : ''; ?>">

<!-- ================= LOGIN ================= -->
<div class="front">

<h1>Rewind Studio</h1>
<p class="subtitle">Photography & Prints</p>

<?php if($success): ?>
<div class="glass-message"><?php echo $success; ?></div>
<?php endif; ?>

<?php if($login_error): ?>
<div class="glass-message"><?php echo $login_error; ?></div>
<?php endif; ?>

<form action="logins.php" method="POST">

<div class="form-group">
<label>Email or Username</label>
<input type="text" name="username" required>
</div>

<div class="form-group">
<label>Password</label>
<div class="password-wrapper">
<input type="password" id="loginPass" name="password" required>
<span class="show-password" onclick="togglePassword('loginPass', this)">Show</span>
</div>

<div class="form-options">
<a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
</div>
</div>

<button type="submit" class="glass-btn">Login</button>
</form>

<!-- SOCIAL -->
<div class="social-section">
<button type="button" class="glass-btn google-btn" onclick="loginWithGoogle()">
<i class="fab fa-google"></i> Login with Google
</button>

<button type="button" class="glass-btn facebook-btn" onclick="loginWithFacebook()">
<i class="fab fa-facebook-f"></i> Login with Facebook
</button>
</div>

<div class="home-btn-container">
<a href="index.php" class="home-btn">Back to Home</a>
</div>

<div class="create-account">
<p>Don’t have an account?</p>
<button class="link-btn" onclick="flipCard(event)">Create an account</button>
</div>

</div>

<!-- ================= SIGNUP ================= -->
<div class="back">

<h1>Create Account</h1>
<p class="subtitle">Join Rewind Studio</p>

<?php if($signup_error): ?>
<div class="glass-message"><?php echo $signup_error; ?></div>
<?php endif; ?>

<form action="signup.php" method="POST">

<div class="form-group">
<label>Username</label>
<input type="text" name="username" required>
</div>

<div class="form-group">
<label>Full Name</label>
<input type="text" name="full_name" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" required>
</div>

<div class="form-group">
<label>Password</label>
<div class="password-wrapper">
<input type="password" id="signupPass" name="password" required>
<span class="show-password" onclick="togglePassword('signupPass', this)">Show</span>
</div>
</div>

<button type="submit" class="glass-btn">Sign Up</button>
</form>

<div class="home-btn-container">
<a href="index.php" class="home-btn">Back to Home</a>
</div>

<div class="create-account">
<p>Already have an account?</p>
<button class="link-btn" onclick="flipCard(event)">Back to Login</button>
</div>

</div>

</div>
</div>

<script>
// FIXED toggle (no conflict)
function togglePassword(id, el){
    const input = document.getElementById(id);

    if(input.type === "password"){
        input.type = "text";
        el.innerText = "Hide";
    } else {
        input.type = "password";
        el.innerText = "Show";
    }
}

// FIXED flip (no jump)
function flipCard(e){
    e.preventDefault();
    document.querySelector(".card").classList.toggle("flip");
}

// Dummy social
function loginWithGoogle(){
    alert("Google login placeholder");
}
function loginWithFacebook(){
    alert("Facebook login placeholder");
}
</script>

</body>
</html>