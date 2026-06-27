<?php
/**
 * admin_register.php — Rewind Studio Admin Registration
 * Security: CSRF token, input validation, password strength, session hardening
 */

// ── Session hardening — MUST come before session_start() ──────────────────
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
    // 'secure' => true,   // uncomment on HTTPS
]);

session_start();

// Redirect if already logged in
if (!empty($_SESSION['admin']['id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

include 'db.php';  // provides $conn (mysqli)

// ── CSRF token ─────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Rate-limit registrations (max 3 per IP per hour) ──────────────────────
$ip           = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$reg_key      = 'reg_attempts_' . md5($ip);
$max_reg      = 3;
$reg_window   = 3600; // 1 hour

if (!isset($_SESSION[$reg_key])) {
    $_SESSION[$reg_key] = ['count' => 0, 'first' => time()];
}

$reg_throttle = &$_SESSION[$reg_key];

// Reset window if expired
if (time() - $reg_throttle['first'] > $reg_window) {
    $reg_throttle = ['count' => 0, 'first' => time()];
}

$reg_locked = $reg_throttle['count'] >= $max_reg;

// ── Process form ───────────────────────────────────────────────────────────
$error   = '';
$success = '';
$fields  = []; // repopulate safe fields on error

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    // CSRF check
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid request. Please refresh and try again.';

    } elseif ($reg_locked) {
        $error = 'Too many registration attempts from this IP. Please try again later.';

    } else {
        $full_name        = trim($_POST['full_name']       ?? '');
        $username         = trim($_POST['username']        ?? '');
        $password         = $_POST['password']             ?? '';
        $confirm_password = $_POST['confirm_password']     ?? '';

        // Repopulate safe fields
        $fields = [
            'full_name' => $full_name,
            'username'  => $username,
        ];

        // ── Validation ────────────────────────────────────────────────────
        if (empty($full_name) || empty($username) || empty($password) || empty($confirm_password)) {
            $error = 'All fields are required.';

        } elseif (strlen($full_name) > 80) {
            $error = 'Full name must be 80 characters or fewer.';

        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error = 'Username must be 3–30 characters and contain only letters, numbers, or underscores.';

        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';

        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter.';

        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number.';

        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';

        } else {
            // Check username uniqueness
            $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = 'That username is already taken. Please choose another.';
                $stmt->close();

            } else {
                $stmt->close();

                // Hash with bcrypt (cost 12)
                $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $ins = $conn->prepare(
                    "INSERT INTO admins (full_name, username, password, created_at)
                     VALUES (?, ?, ?, NOW())"
                );
                $ins->bind_param('sss', $full_name, $username, $hashed);

                if ($ins->execute()) {
                    // Rotate CSRF token after success
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $reg_throttle['count']++;

                    $success = 'Account created successfully! You can now log in.';
                    $fields  = []; // clear fields on success
                } else {
                    $error = 'Registration failed. Please try again.';
                }

                $ins->close();
            }
        }
    }
}

// ── Password strength helper (used by JS) ─────────────────────────────────
// Nothing extra needed here — handled client-side below
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register — Rewind Studio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ── Reset & Tokens (mirrors admin_dashboardv3.css) ─────────────────── */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --gold:        #c9a84c;
            --gold-light:  #e4c97a;
            --gold-dim:    rgba(201,168,76,0.20);
            --gold-bdr:    rgba(201,168,76,0.30);
            --glass-bg:    rgba(255,255,255,0.07);
            --glass-bg2:   rgba(255,255,255,0.11);
            --glass-bdr:   rgba(255,255,255,0.13);
            --white:       #ffffff;
            --off-white:   rgba(255,255,255,0.88);
            --muted:       rgba(255,255,255,0.52);
            --c-green:     #38d39f;
            --c-red:       #ff6b6b;
            --c-yellow:    #ffd166;
            --c-blue:      #60a5fa;
            --radius-sm:   10px;
            --radius-md:   16px;
            --radius-lg:   22px;
            --blur:        blur(18px);
            --transition:  0.26s ease;
            --shadow:      0 8px 32px rgba(0,0,0,0.42);
            --shadow-lg:   0 24px 60px rgba(0,0,0,0.55);
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
            padding: 24px;
            background:
                linear-gradient(135deg, rgba(0,0,0,0.88), rgba(10,10,10,0.96)),
                url("486603552_1072841941528975_1520142067500954297_n.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 20% 30%, rgba(201,168,76,0.06) 0%, transparent 70%),
                radial-gradient(ellipse 40% 60% at 80% 70%, rgba(201,168,76,0.04) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── Wrapper ─────────────────────────────────────────────────────────── */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            display: flex;
            flex-direction: column;
        }

        /* ── Card ────────────────────────────────────────────────────────────── */
        .login-card {
            background: var(--glass-bg);
            backdrop-filter: var(--blur);
            -webkit-backdrop-filter: var(--blur);
            border: 1px solid var(--glass-bdr);
            border-top: 2px solid var(--gold-bdr);
            border-radius: var(--radius-lg);
            padding: 44px 40px 40px;
            box-shadow: var(--shadow-lg);
            animation: fadeUp 0.45s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Brand ───────────────────────────────────────────────────────────── */
        .brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            margin-bottom: 32px;
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gold);
            box-shadow: 0 0 20px rgba(201,168,76,0.35);
        }

        .brand-logo-fallback {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-dim), rgba(201,168,76,0.35));
            border: 2px solid var(--gold);
            box-shadow: 0 0 20px rgba(201,168,76,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: var(--gold);
        }

        .brand-text { text-align: center; }

        .brand-eyebrow {
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.55rem;
            font-weight: 700;
            color: var(--white);
            letter-spacing: 0.01em;
            line-height: 1.15;
        }

        /* ── Alert ───────────────────────────────────────────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 22px;
            line-height: 1.5;
        }

        .alert i { margin-top: 2px; flex-shrink: 0; }

        .alert-error   { background: rgba(255,107,107,0.12); border: 1px solid rgba(255,107,107,0.28); color: #ff8f8f; }
        .alert-success { background: rgba(56,211,159,0.12);  border: 1px solid rgba(56,211,159,0.28);  color: var(--c-green); }

        /* ── Form groups ─────────────────────────────────────────────────────── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 16px;
        }

        .form-label {
            font-size: 0.78rem;
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
            font-size: 0.82rem;
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
            font-size: 0.92rem;
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

        /* Valid / invalid states */
        .form-input.is-valid   { border-color: rgba(56,211,159,0.45);  }
        .form-input.is-invalid { border-color: rgba(255,107,107,0.45); }

        .field-hint {
            font-size: 0.74rem;
            color: var(--muted);
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .field-hint.hint-ok  { color: var(--c-green); }
        .field-hint.hint-err { color: var(--c-red); }

        /* Password toggle */
        .pw-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 0.85rem;
            padding: 4px;
            transition: color var(--transition);
            line-height: 1;
        }

        .pw-toggle:hover { color: var(--gold-light); }

        /* ── Password strength meter ─────────────────────────────────────────── */
        .strength-wrap {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .strength-bars {
            display: flex;
            gap: 4px;
        }

        .strength-bar {
            flex: 1;
            height: 3px;
            border-radius: 2px;
            background: rgba(255,255,255,0.10);
            transition: background 0.3s ease;
        }

        .strength-bar.s1 { background: var(--c-red); }
        .strength-bar.s2 { background: var(--c-yellow); }
        .strength-bar.s3 { background: var(--c-blue); }
        .strength-bar.s4 { background: var(--c-green); }

        .strength-label {
            font-size: 0.72rem;
            color: var(--muted);
            letter-spacing: 0.05em;
            transition: color 0.3s;
        }

        /* Password rules checklist */
        .pw-rules {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 12px;
            margin-top: 6px;
        }

        .pw-rule {
            font-size: 0.74rem;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color var(--transition);
        }

        .pw-rule i { font-size: 0.65rem; }
        .pw-rule.pass { color: var(--c-green); }

        /* ── Submit button ───────────────────────────────────────────────────── */
        .btn-login {
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
            margin-top: 8px;
            transition: transform var(--transition), box-shadow var(--transition),
                        filter var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-login::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity var(--transition);
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(201,168,76,0.40);
            filter: brightness(1.06);
        }

        .btn-login:hover:not(:disabled)::after { opacity: 1; }
        .btn-login:disabled { opacity: 0.45; cursor: not-allowed; }

        /* ── Divider ─────────────────────────────────────────────────────────── */
        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 26px 0 20px;
            color: rgba(255,255,255,0.20);
            font-size: 0.75rem;
            letter-spacing: 0.08em;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }

        /* ── Footer ──────────────────────────────────────────────────────────── */
        .login-footer {
            text-align: center;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .login-footer a {
            color: var(--gold-light);
            text-decoration: none;
            font-weight: 600;
            transition: color var(--transition);
        }

        .login-footer a:hover { color: var(--gold); }

        /* ── Security badge ──────────────────────────────────────────────────── */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 20px;
            font-size: 0.72rem;
            color: rgba(255,255,255,0.28);
            letter-spacing: 0.06em;
        }

        .security-badge i { color: rgba(201,168,76,0.45); font-size: 0.7rem; }

        /* ── Focus visible ───────────────────────────────────────────────────── */
        :focus-visible {
            outline: 2px solid var(--gold);
            outline-offset: 3px;
            border-radius: 4px;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        @media (max-width: 480px) {
            .login-card { padding: 36px 22px 30px; }
            body { padding: 16px; align-items: flex-start; padding-top: 32px; }
            .pw-rules { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- Brand -->
        <div class="brand">
            <?php $logo_path = 'logo.png'; if (file_exists($logo_path)): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Rewind Studio" class="brand-logo">
            <?php else: ?>
                <div class="brand-logo-fallback" aria-hidden="true">
                    <i class="fa-solid fa-film"></i>
                </div>
            <?php endif; ?>
            <div class="brand-text">
                <div class="brand-eyebrow">Admin Portal</div>
                <h1 class="brand-title">Create Account</h1>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php elseif ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= htmlspecialchars($success) ?> <a href="admin_login.php" style="color:inherit;font-weight:700;text-decoration:underline;">Sign in now →</a></span>
            </div>
        <?php endif; ?>

        <!-- Registration form -->
        <?php if (!$success): ?>
        <form method="POST" action="" autocomplete="off" novalidate id="reg-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- Full Name -->
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <div class="input-wrap">
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-input"
                        placeholder="e.g. Juan dela Cruz"
                        value="<?= htmlspecialchars($fields['full_name'] ?? '') ?>"
                        maxlength="80"
                        autocomplete="name"
                        required
                    >
                    <i class="fa-solid fa-id-card input-icon" aria-hidden="true"></i>
                </div>
            </div>

            <!-- Username -->
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-wrap">
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        placeholder="3–30 chars, letters / numbers / _"
                        value="<?= htmlspecialchars($fields['username'] ?? '') ?>"
                        maxlength="30"
                        autocomplete="username"
                        pattern="^[a-zA-Z0-9_]{3,30}$"
                        required
                    >
                    <i class="fa-solid fa-at input-icon" aria-hidden="true"></i>
                </div>
                <span class="field-hint" id="username-hint" aria-live="polite"></span>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="Min 8 chars, 1 uppercase, 1 number"
                        maxlength="128"
                        autocomplete="new-password"
                        required
                    >
                    <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                    <button type="button" class="pw-toggle" aria-label="Show / hide password" onclick="togglePw('password','eye1')">
                        <i class="fa-solid fa-eye" id="eye1"></i>
                    </button>
                </div>

                <!-- Strength meter -->
                <div class="strength-wrap" id="strength-wrap" style="display:none;">
                    <div class="strength-bars">
                        <div class="strength-bar" id="sb1"></div>
                        <div class="strength-bar" id="sb2"></div>
                        <div class="strength-bar" id="sb3"></div>
                        <div class="strength-bar" id="sb4"></div>
                    </div>
                    <span class="strength-label" id="strength-label">Enter a password</span>

                    <!-- Rules checklist -->
                    <div class="pw-rules" aria-live="polite">
                        <span class="pw-rule" id="rule-len"><i class="fa-solid fa-circle"></i> At least 8 chars</span>
                        <span class="pw-rule" id="rule-upper"><i class="fa-solid fa-circle"></i> Uppercase letter</span>
                        <span class="pw-rule" id="rule-num"><i class="fa-solid fa-circle"></i> Number</span>
                        <span class="pw-rule" id="rule-special"><i class="fa-solid fa-circle"></i> Special char</span>
                    </div>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-input"
                        placeholder="Repeat your password"
                        maxlength="128"
                        autocomplete="new-password"
                        required
                    >
                    <i class="fa-solid fa-lock-open input-icon" aria-hidden="true"></i>
                    <button type="button" class="pw-toggle" aria-label="Show / hide confirm password" onclick="togglePw('confirm_password','eye2')">
                        <i class="fa-solid fa-eye" id="eye2"></i>
                    </button>
                </div>
                <span class="field-hint" id="confirm-hint" aria-live="polite"></span>
            </div>

            <!-- Submit -->
            <button type="submit" name="register" class="btn-login" id="submit-btn">
                <i class="fa-solid fa-user-plus"></i>
                Create Account
            </button>
        </form>
        <?php endif; ?>

        <div class="divider">OR</div>

        <div class="login-footer">
            Already have an account?
            <a href="admin_login.php">Sign in here</a>
        </div>

    </div><!-- /.login-card -->

    <div class="security-badge" aria-hidden="true">
        <i class="fa-solid fa-shield-halved"></i>
        Protected &bull; CSRF secured &bull; Rate limited
    </div>
</div>

<script>
// ── Password visibility toggle ─────────────────────────────────────────────
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}

// ── Password strength meter ────────────────────────────────────────────────
const pwInput     = document.getElementById('password');
const confirmInput= document.getElementById('confirm_password');
const strengthWrap= document.getElementById('strength-wrap');
const bars        = [document.getElementById('sb1'), document.getElementById('sb2'),
                     document.getElementById('sb3'), document.getElementById('sb4')];
const strengthLbl = document.getElementById('strength-label');

const rules = {
    len:     { el: document.getElementById('rule-len'),     test: v => v.length >= 8 },
    upper:   { el: document.getElementById('rule-upper'),   test: v => /[A-Z]/.test(v) },
    num:     { el: document.getElementById('rule-num'),     test: v => /[0-9]/.test(v) },
    special: { el: document.getElementById('rule-special'), test: v => /[^a-zA-Z0-9]/.test(v) },
};

const levels = [
    { label: 'Too weak',  color: 'var(--c-red)',    cls: 's1' },
    { label: 'Weak',      color: 'var(--c-red)',    cls: 's1' },
    { label: 'Fair',      color: 'var(--c-yellow)', cls: 's2' },
    { label: 'Good',      color: 'var(--c-blue)',   cls: 's3' },
    { label: 'Strong',    color: 'var(--c-green)',  cls: 's4' },
];

pwInput.addEventListener('input', function () {
    const v = this.value;

    if (!v) {
        strengthWrap.style.display = 'none';
        return;
    }
    strengthWrap.style.display = 'flex';

    // Score
    let score = 0;
    Object.values(rules).forEach(r => {
        const pass = r.test(v);
        r.el.classList.toggle('pass', pass);
        r.el.querySelector('i').className = pass ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle';
        if (pass) score++;
    });
    if (v.length >= 12) score = Math.min(4, score + 1);

    // Update bars
    bars.forEach((b, i) => {
        b.className = 'strength-bar';
        if (i < score) b.classList.add(levels[score].cls);
    });

    strengthLbl.textContent  = levels[score].label;
    strengthLbl.style.color  = levels[score].color;

    // Also update confirm match
    checkConfirm();
});

// ── Confirm password match ────────────────────────────────────────────────
const confirmHint = document.getElementById('confirm-hint');

function checkConfirm() {
    const v = confirmInput.value;
    if (!v) { confirmHint.textContent = ''; confirmHint.className = 'field-hint'; return; }
    const match = v === pwInput.value;
    confirmInput.classList.toggle('is-valid',   match);
    confirmInput.classList.toggle('is-invalid', !match);
    confirmHint.textContent  = match ? '✓ Passwords match' : '✗ Passwords do not match';
    confirmHint.className    = 'field-hint ' + (match ? 'hint-ok' : 'hint-err');
}

confirmInput.addEventListener('input', checkConfirm);

// ── Username format hint ──────────────────────────────────────────────────
const usernameInput = document.getElementById('username');
const usernameHint  = document.getElementById('username-hint');

usernameInput.addEventListener('input', function () {
    const v = this.value;
    if (!v) { usernameHint.textContent = ''; usernameHint.className = 'field-hint'; return; }
    const ok = /^[a-zA-Z0-9_]{3,30}$/.test(v);
    this.classList.toggle('is-valid',   ok);
    this.classList.toggle('is-invalid', !ok);
    usernameHint.textContent = ok ? '✓ Valid username' : '✗ 3–30 chars: letters, numbers, underscores only';
    usernameHint.className   = 'field-hint ' + (ok ? 'hint-ok' : 'hint-err');
});

// ── Auto-focus ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const fn = document.getElementById('full_name');
    if (fn && !fn.disabled) fn.focus();
});
</script>

</body>
</html>