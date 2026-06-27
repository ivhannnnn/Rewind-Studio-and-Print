<?php
session_start();

/* ── Session messages ─────────────────────────────────────────────────── */
$login_error  = $_SESSION['login_error']   ?? '';
$signup_error = $_SESSION['signup_error']  ?? '';
$success      = $_SESSION['signup_success'] ?? $_SESSION['logout_success'] ?? '';

unset(
    $_SESSION['login_error'],
    $_SESSION['signup_error'],
    $_SESSION['signup_success'],
    $_SESSION['logout_success']
);

// Auto-flip to signup side when signup validation fails
$flip = !empty($signup_error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewind Studio | Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ── Reset & Tokens ──────────────────────────────────────────────── */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --gold:       #c9a84c;
            --gold-light: #e4c97a;
            --gold-dim:   rgba(201,168,76,0.18);
            --gold-bdr:   rgba(201,168,76,0.32);
            --glass-bg:   rgba(255,255,255,0.07);
            --glass-bg2:  rgba(255,255,255,0.11);
            --glass-bdr:  rgba(255,255,255,0.13);
            --white:      #ffffff;
            --off-white:  rgba(255,255,255,0.88);
            --muted:      rgba(255,255,255,0.52);
            --c-green:    #38d39f;
            --c-red:      #ff6b6b;
            --c-yellow:   #ffd166;
            --c-blue:     #9bd0ff;
            --radius-sm:  10px;
            --radius-md:  16px;
            --radius-lg:  22px;
            --blur:       blur(20px);
            --transition: 0.26s ease;
            --shadow-lg:  0 24px 64px rgba(0,0,0,0.55);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            color: var(--white);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 20px;
            overflow-x: hidden;
            background:
                linear-gradient(135deg, rgba(0,0,0,0.86), rgba(8,7,4,0.95)),
                url("backgrounds.png");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Gold ambient glow */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 65% 55% at 15% 25%, rgba(201,168,76,0.07) 0%, transparent 65%),
                radial-gradient(ellipse 45% 55% at 85% 75%, rgba(201,168,76,0.05) 0%, transparent 65%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── Card container (3-D perspective) ───────────────────────────── */
        .card-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 470px;
            perspective: 1800px;
        }

        /* ── Flip card ───────────────────────────────────────────────────── */
        .card {
            position: relative;
            width: 100%;
            min-height: 640px;
            transform-style: preserve-3d;
            transition: transform 0.75s cubic-bezier(0.45, 0.05, 0.15, 1.0);
        }

        .card.flip { transform: rotateY(180deg); }

        /* ── Front & Back shared ─────────────────────────────────────────── */
        .front,
        .back {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            min-height: 100%;
            padding: 42px 38px 36px;
            border-radius: var(--radius-lg);
            background: var(--glass-bg);
            backdrop-filter: var(--blur);
            -webkit-backdrop-filter: var(--blur);
            border: 1px solid var(--glass-bdr);
            border-top: 2px solid var(--gold-bdr);
            box-shadow: var(--shadow-lg);
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .back { transform: rotateY(180deg); }

        /* ── Brand header ────────────────────────────────────────────────── */
        .card-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
        }

        .brand-logo-wrap {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-dim), rgba(201,168,76,0.32));
            border: 2px solid var(--gold);
            box-shadow: 0 0 20px rgba(201,168,76,0.30);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: var(--gold);
            flex-shrink: 0;
        }

        .brand-logo-wrap img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .brand-eyebrow {
            font-size: 0.7rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 600;
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--white);
            letter-spacing: 0.01em;
            line-height: 1.1;
            text-align: center;
        }

        .brand-sub {
            font-size: 0.82rem;
            color: var(--muted);
            text-align: center;
            letter-spacing: 0.04em;
        }

        /* ── Alerts ──────────────────────────────────────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 12px 15px;
            border-radius: var(--radius-sm);
            font-size: 0.84rem;
            font-weight: 500;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .alert i { margin-top: 2px; flex-shrink: 0; font-size: 0.8rem; }

        .alert-success { background: rgba(56,211,159,0.11);  border: 1px solid rgba(56,211,159,0.28);  color: var(--c-green); }
        .alert-error   { background: rgba(255,107,107,0.11); border: 1px solid rgba(255,107,107,0.28); color: #ff8f8f; }

        /* ── Divider ─────────────────────────────────────────────────────── */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: rgba(255,255,255,0.22);
            font-size: 0.72rem;
            letter-spacing: 0.1em;
        }

        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }

        /* ── Form groups ─────────────────────────────────────────────────── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
        }

        .form-label {
            font-size: 0.76rem;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gold);
            font-size: 0.8rem;
            opacity: 0.65;
            pointer-events: none;
            transition: opacity var(--transition);
        }

        .input-wrap:focus-within .input-icon { opacity: 1; }

        .form-input {
            width: 100%;
            padding: 13px 14px 13px 40px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(255,255,255,0.10);
            background: rgba(255,255,255,0.06);
            color: var(--white);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: border-color var(--transition), background var(--transition),
                        box-shadow var(--transition);
        }

        .form-input::placeholder { color: var(--muted); }

        .form-input:focus {
            border-color: var(--gold-bdr);
            background: rgba(255,255,255,0.09);
            box-shadow: 0 0 0 3px var(--gold-dim);
        }

        /* Password field with toggle */
        .form-input.has-toggle { padding-right: 50px; }

        .pw-toggle {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 0.82rem;
            padding: 4px;
            line-height: 1;
            transition: color var(--transition);
        }

        .pw-toggle:hover { color: var(--gold-light); }

        /* Forgot link */
        .forgot-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 5px;
        }

        .forgot-link {
            font-size: 0.76rem;
            color: var(--c-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--transition);
        }

        .forgot-link:hover { color: var(--gold-light); text-decoration: underline; }

        /* ── Primary button ──────────────────────────────────────────────── */
        .btn-primary {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 30px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #1a1200;
            font-family: 'Inter', sans-serif;
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 4px;
            transition: transform var(--transition), box-shadow var(--transition),
                        filter var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(201,168,76,0.38);
            filter: brightness(1.06);
        }

        .btn-primary:hover::after { opacity: 1; }

        /* ── Google button ───────────────────────────────────────────────── */
        .btn-google {
            width: 100%;
            padding: 13px 14px;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,0.14);
            background: rgba(255,255,255,0.07);
            color: var(--off-white);
            font-family: 'Inter', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            transition: background var(--transition), border-color var(--transition),
                        transform var(--transition);
        }

        .btn-google:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.22);
            transform: translateY(-2px);
        }

        .btn-google .g-icon { font-size: 1rem; }

        /* ── Footer area ─────────────────────────────────────────────────── */
        .card-footer {
            margin-top: 22px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .home-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--muted);
            text-decoration: none;
            transition: color var(--transition);
        }

        .home-btn:hover { color: var(--gold-light); }

        .switch-row {
            font-size: 0.82rem;
            color: var(--muted);
            text-align: center;
        }

        .link-btn {
            background: none;
            border: none;
            color: var(--gold-light);
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            padding: 0;
            transition: color var(--transition);
        }

        .link-btn:hover { color: var(--gold); text-decoration: underline; }

        /* ── Focus visible ───────────────────────────────────────────────── */
        :focus-visible {
            outline: 2px solid var(--gold);
            outline-offset: 3px;
            border-radius: 4px;
        }

        /* ── Reduced motion ──────────────────────────────────────────────── */
        @media (prefers-reduced-motion: reduce) {
            .card { transition-duration: 0.01ms !important; }
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ── Mobile ──────────────────────────────────────────────────────── */
        @media (max-width: 500px) {
            .front, .back { padding: 32px 22px 28px; }
            .brand-title  { font-size: 1.45rem; }
            body          { align-items: flex-start; padding-top: 36px; }
            .card         { min-height: 600px; }
        }
    </style>
</head>
<body>

<div class="card-container">
    <div id="authCard" class="card <?= $flip ? 'flip' : '' ?>">

        <!-- ══════════════════════════════════════
             FRONT — LOGIN
        ═══════════════════════════════════════ -->
        <div class="front" aria-label="Login form">

            <!-- Brand -->
            <div class="card-brand">
                <div class="brand-logo-wrap">
                    <?php if (file_exists('logo.png')): ?>
                        <img src="logo.png" alt="Rewind Studio logo">
                    <?php else: ?>
                        <i class="fa-solid fa-film" aria-hidden="true"></i>
                    <?php endif; ?>
                </div>
                <div class="brand-eyebrow">Photography &amp; Prints</div>
                <h1 class="brand-title">Rewind Studio</h1>
                <p class="brand-sub">Sign in to your account</p>
            </div>

            <!-- Success / Error alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($login_error): ?>
                <div class="alert alert-error" role="alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= htmlspecialchars($login_error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login form -->
            <form action="logins.php" method="POST" autocomplete="on" novalidate>

                <div class="form-group">
                    <label class="form-label" for="login-user">Email or Username</label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            id="login-user"
                            name="username"
                            class="form-input"
                            placeholder="you@email.com"
                            autocomplete="username"
                            required
                        >
                        <i class="fa-solid fa-user input-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="loginPass">Password</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="loginPass"
                            name="password"
                            class="form-input has-toggle"
                            placeholder="Your password"
                            autocomplete="current-password"
                            required
                        >
                        <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                        <button type="button" class="pw-toggle" aria-label="Show / hide password"
                                onclick="togglePw('loginPass','eye-login')">
                            <i class="fa-solid fa-eye" id="eye-login"></i>
                        </button>
                    </div>
                    <div class="forgot-row">
                        <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    Sign In
                </button>

            </form>

            <div class="section-divider">OR</div>

            <!-- Google login -->
            <button type="button" class="btn-google" onclick="loginWithGoogle()">
                <i class="fab fa-google g-icon"></i>
                Continue with Google
            </button>

            <!-- Footer -->
            <div class="card-footer">
                <a href="index.php" class="home-btn">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Back to Home
                </a>
                <div class="switch-row">
                    Don't have an account?&nbsp;
                    <button type="button" class="link-btn" onclick="flipCard()">
                        Create one free
                    </button>
                </div>
            </div>

        </div><!-- /front -->


        <!-- ══════════════════════════════════════
             BACK — SIGN UP
        ═══════════════════════════════════════ -->
        <div class="back" aria-label="Sign up form">

            <!-- Brand -->
            <div class="card-brand">
                <div class="brand-logo-wrap">
                    <?php if (file_exists('logo.png')): ?>
                        <img src="logo.png" alt="Rewind Studio logo">
                    <?php else: ?>
                        <i class="fa-solid fa-film" aria-hidden="true"></i>
                    <?php endif; ?>
                </div>
                <div class="brand-eyebrow">Join Rewind Studio</div>
                <h1 class="brand-title">Create Account</h1>
                <p class="brand-sub">It's free — takes 30 seconds</p>
            </div>

            <!-- Signup error alert -->
            <?php if ($signup_error): ?>
                <div class="alert alert-error" role="alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= htmlspecialchars($signup_error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Signup form -->
            <form action="signup.php" method="POST" autocomplete="off" novalidate>

                <div class="form-group">
                    <label class="form-label" for="su-username">Username</label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            id="su-username"
                            name="username"
                            class="form-input"
                            placeholder="e.g. juan_dela_cruz"
                            maxlength="30"
                            autocomplete="username"
                            required
                        >
                        <i class="fa-solid fa-at input-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="su-fullname">Full Name</label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            id="su-fullname"
                            name="full_name"
                            class="form-input"
                            placeholder="Your full name"
                            maxlength="80"
                            autocomplete="name"
                            required
                        >
                        <i class="fa-solid fa-id-card input-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="su-email">Email</label>
                    <div class="input-wrap">
                        <input
                            type="email"
                            id="su-email"
                            name="email"
                            class="form-input"
                            placeholder="you@email.com"
                            maxlength="120"
                            autocomplete="email"
                            required
                        >
                        <i class="fa-solid fa-envelope input-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="signupPass">Password</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="signupPass"
                            name="password"
                            class="form-input has-toggle"
                            placeholder="Min 8 chars"
                            maxlength="128"
                            autocomplete="new-password"
                            required
                        >
                        <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                        <button type="button" class="pw-toggle" aria-label="Show / hide password"
                                onclick="togglePw('signupPass','eye-signup')">
                            <i class="fa-solid fa-eye" id="eye-signup"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-user-plus"></i>
                    Create Account
                </button>

            </form>

            <!-- Footer -->
            <div class="card-footer">
                <a href="index.php" class="home-btn">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Back to Home
                </a>
                <div class="switch-row">
                    Already have an account?&nbsp;
                    <button type="button" class="link-btn" onclick="flipCard()">
                        Sign in instead
                    </button>
                </div>
            </div>

        </div><!-- /back -->

    </div><!-- /card -->
</div><!-- /card-container -->

<script>
/* ── Password visibility toggle ─────────────────────────────────────── */
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}

/* ── Card flip ──────────────────────────────────────────────────────── */
function flipCard() {
    document.getElementById('authCard').classList.toggle('flip');
}

/* ── Google login (placeholder) ─────────────────────────────────────── */
function loginWithGoogle() {
    window.location.href = 'google_auth.php?action=login';
}

/* ── Auto-focus ──────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    const isFlipped = document.getElementById('authCard').classList.contains('flip');
    const target = isFlipped
        ? document.getElementById('su-username')
        : document.getElementById('login-user');
    if (target) target.focus();
});
</script>

</body>
</html>