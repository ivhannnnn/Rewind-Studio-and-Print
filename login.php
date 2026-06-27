```php
<?php
session_start();

/* ===============================
   SESSION MESSAGES
================================ */
$login_error  = $_SESSION['login_error'] ?? "";
$signup_error = $_SESSION['signup_error'] ?? "";
$success      = $_SESSION['signup_success']
                ?? $_SESSION['logout_success']
                ?? "";

unset(
    $_SESSION['login_error'],
    $_SESSION['signup_error'],
    $_SESSION['signup_success'],
    $_SESSION['logout_success']
);

/* Automatically open signup side
   when signup validation fails */
$flip = !empty($signup_error);
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Rewind Studio | Login</title>

<link rel="stylesheet" href="login_v8.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

</head>

<body>

<div class="card-container">

    <div id="authCard"
         class="card <?php echo $flip ? 'flip' : ''; ?>">

        <!-- =========================
             LOGIN SIDE
        ========================== -->
        <div class="front">

            <div class="card-content">

                <h1>Rewind Studio</h1>

                <p class="subtitle">
                    Photography & Prints
                </p>

                <?php if($success): ?>
                    <div class="glass-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if($login_error): ?>
                    <div class="glass-message">
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>

                <form action="logins.php" method="POST">

                    <div class="form-group">

                        <label>Email or Username</label>

                        <input
                            type="text"
                            name="username"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>Password</label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="loginPass"
                                name="password"
                                required
                            >

                            <span
                                class="show-password"
                                onclick="togglePassword('loginPass', this)">
                                Show
                            </span>

                        </div>

                        <div class="form-options">
                            <a href="forgot_password.php"
                               class="forgot-link">
                                Forgot Password?
                            </a>
                        </div>

                    </div>

                    <button
                        type="submit"
                        class="glass-btn">
                        Login
                    </button>

                </form>

                <div class="social-section">

                    <button
                        type="button"
                        class="glass-btn google-btn"
                        onclick="loginWithGoogle()">

                        <i class="fab fa-google"></i>
                        Login with Google

                    </button>

                </div>

                <div class="home-btn-container">

                    <a href="index.php"
                       class="home-btn">

                        Back to Home

                    </a>

                </div>

                <div class="create-account">

                    <p>Don't have an account?</p>

                    <button
                        type="button"
                        class="link-btn"
                        onclick="flipCard()">

                        Create an Account

                    </button>

                </div>

            </div>

        </div>

        <!-- =========================
             SIGNUP SIDE
        ========================== -->
        <div class="back">

            <div class="card-content">

                <h1>Create Account</h1>

                <p class="subtitle">
                    Join Rewind Studio
                </p>

                <?php if($signup_error): ?>
                    <div class="glass-message">
                        <?php echo htmlspecialchars($signup_error); ?>
                    </div>
                <?php endif; ?>

                <form action="signup.php" method="POST">

                    <div class="form-group">

                        <label>Username</label>

                        <input
                            type="text"
                            name="username"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>Full Name</label>

                        <input
                            type="text"
                            name="full_name"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>Email</label>

                        <input
                            type="email"
                            name="email"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>Password</label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="signupPass"
                                name="password"
                                required
                            >

                            <span
                                class="show-password"
                                onclick="togglePassword('signupPass', this)">
                                Show
                            </span>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="glass-btn">
                        Sign Up
                    </button>

                </form>

                <div class="home-btn-container">

                    <a href="index.php"
                       class="home-btn">

                        Back to Home

                    </a>

                </div>

                <div class="create-account">

                    <p>Already have an account?</p>

                    <button
                        type="button"
                        class="link-btn"
                        onclick="flipCard()">

                        Back to Login

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

/* ===============================
   SHOW / HIDE PASSWORD
================================ */
function togglePassword(id, element){

    const input = document.getElementById(id);

    if(input.type === "password"){
        input.type = "text";
        element.innerText = "Hide";
    }else{
        input.type = "password";
        element.innerText = "Show";
    }
}

/* ===============================
   CARD FLIP
================================ */
function flipCard(){

    document
        .getElementById("authCard")
        .classList
        .toggle("flip");
}

/* ===============================
   GOOGLE LOGIN
================================ */
function loginWithGoogle(){

    alert("Google Login Placeholder");

}

</script>

</body>
</html>
```
