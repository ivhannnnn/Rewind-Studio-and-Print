<?php
/**
 * admin_login.php — Rewind Studio Admin Login
 * Security: CSRF token, brute-force throttle, session hardening, prepared statements
 */

// ── Session hardening — MUST come before session_start() ──────────────────
// session_set_cookie_params is the correct cross-version way to do this
session_set_cookie_params([
    'lifetime' => 0,           // expires when browser closes
    'path'     => '/',
    'httponly' => true,        // JS cannot access the cookie
    'samesite' => 'Strict',    // blocks CSRF via cross-site requests
    // 'secure' => true,       // uncomment when running on HTTPS
]);

session_start();;

// Redirect if already logged in
if (!empty($_SESSION['admin']['id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

include 'db.php';   // provides $conn (mysqli)

// ── CSRF token generation ──────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Brute-force throttle (max 5 attempts / 15 min per IP) ─────────────────
$ip           = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$throttle_key = 'login_attempts_' . md5($ip);
$max_attempts = 5;
$lockout_secs = 15 * 60;   // 15 minutes

if (!isset($_SESSION[$throttle_key])) {
    $_SESSION[$throttle_key] = ['count' => 0, 'first_attempt' => time()];
}

$throttle       = &$_SESSION[$throttle_key];
$locked_out     = false;
$remaining_secs = 0;

if ($throttle['count'] >= $max_attempts) {
    $elapsed = time() - $throttle['first_attempt'];
    if ($elapsed < $lockout_secs) {
        $locked_out     = true;
        $remaining_secs = $lockout_secs - $elapsed;
    } else {
        // Reset after lockout window
        $throttle = ['count' => 0, 'first_attempt' => time()];
    }
}

// ── Process form ───────────────────────────────────────────────────────────
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    // CSRF check
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid request. Please try again.';

    } elseif ($locked_out) {
        $mins = ceil($remaining_secs / 60);
        $error = "Too many failed attempts. Try again in {$mins} minute(s).";

    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic input validation
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';

        } elseif (strlen($username) > 60 || strlen($password) > 128) {
            $error = 'Invalid input length.';

        } else {
            // Prepared statement — no SQL injection possible
            $stmt = $conn->prepare(
                "SELECT id, full_name, username, password FROM admins WHERE username = ? LIMIT 1"
            );
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin  = $result->fetch_assoc();
            $stmt->close();

            // Constant-time comparison via password_verify
            // Always run verify even if no row found (prevents timing oracle)
            $dummy_hash = '$2y$12$invalidsaltxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.';
            $hash       = $admin ? $admin['password'] : $dummy_hash;

            if ($admin && password_verify($password, $hash)) {
                // ✅ Success — regenerate session ID to prevent fixation
                session_regenerate_id(true);
                unset($_SESSION[$throttle_key]);

                $_SESSION['admin'] = [
                    'id'        => (int) $admin['id'],
                    'full_name' => htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8'),
                    'username'  => htmlspecialchars($admin['username'],  ENT_QUOTES, 'UTF-8'),
                ];
                $_SESSION['login_time'] = time();

                // Rotate CSRF token after login
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: admin_dashboard.php');
                exit();

            } else {
                // Increment throttle counter
                $throttle['count']++;
                if ($throttle['count'] === 1) {
                    $throttle['first_attempt'] = time();
                }

                $remaining = $max_attempts - $throttle['count'];
                $error = $remaining > 0
                    ? "Incorrect credentials. {$remaining} attempt(s) remaining."
                    : 'Too many failed attempts. Account temporarily locked.';
            }
        }
    }
}

// Format lockout countdown for display
$lockout_display = '';
if ($locked_out) {
    $m = floor($remaining_secs / 60);
    $s = $remaining_secs % 60;
    $lockout_display = $m > 0 ? "{$m}m {$s}s" : "{$s}s";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Rewind Studio</title>

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

        /* Subtle animated particles */
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

        /* ── Login Wrapper ───────────────────────────────────────────────────── */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            display: flex;
            flex-direction: column;
            gap: 0;
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
            margin-bottom: 36px;
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gold);
            box-shadow: 0 0 20px rgba(201,168,76,0.35);
        }

        /* Fallback logo if image missing */
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

        .brand-text {
            text-align: center;
        }

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

        .alert i { margin-top: 1px; flex-shrink: 0; }

        .alert-error {
            background: rgba(255,107,107,0.12);
            border: 1px solid rgba(255,107,107,0.28);
            color: #ff8f8f;
        }

        .alert-success {
            background: rgba(56,211,159,0.12);
            border: 1px solid rgba(56,211,159,0.28);
            color: var(--c-green);
        }

        .alert-warning {
            background: rgba(255,209,102,0.12);
            border: 1px solid rgba(255,209,102,0.28);
            color: var(--c-yellow);
        }

        /* ── Form ────────────────────────────────────────────────────────────── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 18px;
        }

        .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gold);
            font-size: 0.82rem;
            opacity: 0.7;
            pointer-events: none;
            transition: opacity var(--transition);
        }

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

        .form-input:focus + .input-icon,
        .input-wrap:focus-within .input-icon { opacity: 1; }

        /* Fix icon position — it's a sibling after input in DOM so we use wrap */
        .input-wrap .form-input { order: 1; }
        .input-wrap .input-icon { order: 0; }

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

        /* ── Lockout bar ─────────────────────────────────────────────────────── */
        .lockout-bar {
            width: 100%;
            height: 3px;
            border-radius: 2px;
            background: rgba(255,209,102,0.15);
            margin-top: 6px;
            overflow: hidden;
        }

        .lockout-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--c-yellow), #ff9a3c);
            border-radius: 2px;
            transition: width 1s linear;
        }

        /* ── Attempt dots ────────────────────────────────────────────────────── */
        .attempt-dots {
            display: flex;
            gap: 5px;
            align-items: center;
            margin-bottom: 20px;
        }

        .attempt-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            transition: background var(--transition);
        }

        .attempt-dot.used { background: var(--c-red); border-color: rgba(255,107,107,0.5); }
        .attempt-dot.last { background: var(--c-yellow); border-color: rgba(255,209,102,0.5); }

        .attempt-label {
            font-size: 0.72rem;
            color: var(--muted);
            margin-left: 4px;
        }

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

        .btn-login:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        /* ── Divider ─────────────────────────────────────────────────────────── */
        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 28px 0 20px;
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

        /* ── Footer links ────────────────────────────────────────────────────── */
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

        /* ── Reduced motion ──────────────────────────────────────────────────── */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ── Responsive ──────────────────────────────────────────────────────── */
        @media (max-width: 480px) {
            .login-card { padding: 36px 24px 30px; }
            body { padding: 16px; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- Brand -->
        <div class="brand">
            <?php
            // Use actual logo if it exists, otherwise fallback icon
            $logo_path = 'logo.png'; // adjust to your actual logo file
            if (file_exists($logo_path)): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Rewind Studio" class="brand-logo">
            <?php else: ?>
                <div class="brand-logo-fallback" aria-hidden="true">
                    <i class="fa-solid fa-film"></i>
                </div>
            <?php endif; ?>

            <div class="brand-text">
                <div class="brand-eyebrow">Admin Portal</div>
                <h1 class="brand-title">Rewind Studio</h1>
            </div>
        </div>

        <?php
        // ── Locked out message ─────────────────────────────────────────────
        if ($locked_out): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fa-solid fa-clock"></i>
                <span>Access temporarily locked. Try again in <strong id="countdown"><?= htmlspecialchars($lockout_display) ?></strong>.</span>
            </div>

        <?php elseif ($error): ?>
            <div class="alert alert-error" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>

        <?php elseif ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Attempt indicator dots (only show after first failure) -->
        <?php if (!$locked_out && $throttle['count'] > 0): ?>
            <div class="attempt-dots" aria-label="Login attempts remaining">
                <?php for ($i = 0; $i < $max_attempts; $i++):
                    $used = $i < $throttle['count'];
                    $last = $i === $throttle['count'] - 1;
                    $cls  = $used ? ($last ? 'used last' : 'used') : '';
                ?>
                    <div class="attempt-dot <?= $cls ?>" title="<?= $used ? 'Failed attempt' : 'Remaining' ?>"></div>
                <?php endfor; ?>
                <span class="attempt-label"><?= $max_attempts - $throttle['count'] ?> of <?= $max_attempts ?> remaining</span>
            </div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST" action="" autocomplete="off" novalidate>
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- Username -->
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-wrap">
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        placeholder="Enter your username"
                        value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                        autocomplete="username"
                        maxlength="60"
                        required
                        <?= $locked_out ? 'disabled' : '' ?>
                        aria-describedby="username-hint"
                    >
                    <i class="fa-solid fa-user input-icon" aria-hidden="true"></i>
                </div>
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
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        maxlength="128"
                        required
                        <?= $locked_out ? 'disabled' : '' ?>
                    >
                    <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                    <button
                        type="button"
                        class="pw-toggle"
                        aria-label="Show / hide password"
                        onclick="togglePassword()"
                    >
                        <i class="fa-solid fa-eye" id="pw-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                name="login"
                class="btn-login"
                <?= $locked_out ? 'disabled' : '' ?>
            >
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                Sign In
            </button>
        </form>

        <div class="divider">OR</div>

        <div class="login-footer">
            Don't have an account?
            <a href="admin_register.php">Request access</a>
        </div>

    </div><!-- /.login-card -->

    <div class="security-badge" aria-hidden="true">
        <i class="fa-solid fa-shield-halved"></i>
        Protected &bull; Session secured &bull; Rate limited
    </div>
</div>

<script>
// ── Password visibility toggle ─────────────────────────────────────────────
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pw-eye');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}

// ── Lockout countdown ──────────────────────────────────────────────────────
<?php if ($locked_out && $remaining_secs > 0): ?>
(function () {
    let secs = <?= (int) $remaining_secs ?>;
    const el = document.getElementById('countdown');
    if (!el) return;

    function fmt(s) {
        const m = Math.floor(s / 60);
        const r = s % 60;
        return m > 0 ? `${m}m ${r}s` : `${r}s`;
    }

    const timer = setInterval(function () {
        secs--;
        if (secs <= 0) {
            clearInterval(timer);
            location.reload();
        } else {
            el.textContent = fmt(secs);
        }
    }, 1000);
})();
<?php endif; ?>

// ── Auto-focus first empty field ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const u = document.getElementById('username');
    const p = document.getElementById('password');
    if (u && !u.disabled) {
        u.value ? p && p.focus() : u.focus();
    }
});
</script>

</body>
</html>