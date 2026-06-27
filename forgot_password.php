<?php
/**
 * forgot_password.php — Rewind Studio
 * Shows the "enter your email" form.
 * send_code.php handles the actual email + token logic.
 */
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Pull flash messages set by send_code.php
$message      = $_SESSION['fp_message']      ?? '';
$message_type = $_SESSION['fp_message_type'] ?? 'error'; // 'success' | 'error'
unset($_SESSION['fp_message'], $_SESSION['fp_message_type']);

// Keep email field repopulated on error
$old_email = htmlspecialchars($_SESSION['fp_old_email'] ?? '', ENT_QUOTES, 'UTF-8');
unset($_SESSION['fp_old_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Rewind Studio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --gold:       #c9a84c;
            --gold-light: #e4c97a;
            --gold-dim:   rgba(201,168,76,0.18);
            --gold-bdr:   rgba(201,168,76,0.32);
            --glass-bg:   rgba(255,255,255,0.07);
            --glass-bdr:  rgba(255,255,255,0.13);
            --white:      #ffffff;
            --off-white:  rgba(255,255,255,0.88);
            --muted:      rgba(255,255,255,0.52);
            --c-green:    #38d39f;
            --c-red:      #ff6b6b;
            --radius-sm:  10px;
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
            background:
                linear-gradient(135deg, rgba(0,0,0,0.86), rgba(8,7,4,0.95)),
                url("backgrounds.png");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

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

        /* ── Wrapper ──────────────────────────────────────────────────────── */
        .fp-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            display: flex;
            flex-direction: column;
            animation: fadeUp 0.42s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Card ─────────────────────────────────────────────────────────── */
        .fp-card {
            background: var(--glass-bg);
            backdrop-filter: var(--blur);
            -webkit-backdrop-filter: var(--blur);
            border: 1px solid var(--glass-bdr);
            border-top: 2px solid var(--gold-bdr);
            border-radius: var(--radius-lg);
            padding: 44px 40px 40px;
            box-shadow: var(--shadow-lg);
        }

        /* ── Brand ────────────────────────────────────────────────────────── */
        .brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .brand-logo-wrap {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-dim), rgba(201,168,76,0.32));
            border: 2px solid var(--gold);
            box-shadow: 0 0 20px rgba(201,168,76,0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: var(--gold);
        }

        .brand-logo-wrap img {
            width: 100%; height: 100%;
            border-radius: 50%; object-fit: cover;
        }

        .brand-eyebrow {
            font-size: 0.70rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 600;
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.55rem;
            font-weight: 700;
            color: var(--white);
            text-align: center;
            line-height: 1.15;
        }

        .brand-sub {
            font-size: 0.82rem;
            color: var(--muted);
            text-align: center;
            max-width: 300px;
            line-height: 1.55;
        }

        /* ── Step indicator ───────────────────────────────────────────────── */
        .steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 28px;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.05);
            color: var(--muted);
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition);
        }

        .step.active  .step-circle { border-color: var(--gold); background: var(--gold-dim); color: var(--gold-light); }
        .step.done    .step-circle { border-color: var(--c-green); background: rgba(56,211,159,0.12); color: var(--c-green); }

        .step-label {
            font-size: 0.65rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            white-space: nowrap;
        }

        .step.active .step-label { color: var(--gold); }
        .step.done   .step-label { color: var(--c-green); }

        .step-line {
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.10);
            margin: 0 8px;
            margin-bottom: 20px; /* align with circles */
        }

        /* ── Alert ────────────────────────────────────────────────────────── */
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

        .alert i { margin-top: 2px; flex-shrink: 0; font-size: 0.8rem; }
        .alert-error   { background: rgba(255,107,107,0.11); border: 1px solid rgba(255,107,107,0.28); color: #ff8f8f; }
        .alert-success { background: rgba(56,211,159,0.11);  border: 1px solid rgba(56,211,159,0.28);  color: var(--c-green); }

        /* ── Form ─────────────────────────────────────────────────────────── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 18px;
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

        /* ── Submit button ────────────────────────────────────────────────── */
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
        .btn-primary:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }

        /* Loading spinner inside button */
        .btn-primary .spinner {
            display: none;
            width: 16px; height: 16px;
            border: 2px solid rgba(26,18,0,0.3);
            border-top-color: #1a1200;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Footer links ─────────────────────────────────────────────────── */
        .fp-footer {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: var(--muted);
            text-decoration: none;
            transition: color var(--transition);
        }

        .back-link:hover { color: var(--gold-light); }

        /* ── Security badge ───────────────────────────────────────────────── */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 18px;
            font-size: 0.72rem;
            color: rgba(255,255,255,0.26);
            letter-spacing: 0.06em;
        }

        .security-badge i { color: rgba(201,168,76,0.4); font-size: 0.7rem; }

        :focus-visible { outline: 2px solid var(--gold); outline-offset: 3px; border-radius: 4px; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }

        @media (max-width: 480px) {
            .fp-card { padding: 34px 22px 28px; }
            body { align-items: flex-start; padding-top: 36px; }
        }
    </style>
</head>
<body>

<div class="fp-wrapper">
    <div class="fp-card">

        <!-- Brand -->
        <div class="brand">
            <div class="brand-logo-wrap">
                <?php if (file_exists('logo.png')): ?>
                    <img src="logo.png" alt="Rewind Studio">
                <?php else: ?>
                    <i class="fa-solid fa-film" aria-hidden="true"></i>
                <?php endif; ?>
            </div>
            <div class="brand-eyebrow">Password Recovery</div>
            <h1 class="brand-title">Forgot Password?</h1>
            <p class="brand-sub">Enter your email and we'll send you a reset link — valid for 15 minutes.</p>
        </div>

        <!-- Step indicator -->
        <div class="steps" aria-label="Recovery steps">
            <div class="step active">
                <div class="step-circle">1</div>
                <div class="step-label">Email</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-circle">2</div>
                <div class="step-label">Verify</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-circle">3</div>
                <div class="step-label">Reset</div>
            </div>
        </div>

        <!-- Flash message -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>" role="alert">
                <i class="fa-solid fa-<?= $message_type === 'success' ? 'circle-check' : 'triangle-exclamation' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="send_code.php" id="fp-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-wrap">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="you@email.com"
                        value="<?= $old_email ?>"
                        autocomplete="email"
                        maxlength="120"
                        required
                        autofocus
                    >
                    <i class="fa-solid fa-envelope input-icon" aria-hidden="true"></i>
                </div>
            </div>

            <button type="submit" class="btn-primary" id="submit-btn">
                <span class="spinner" id="spinner"></span>
                <i class="fa-solid fa-paper-plane" id="btn-icon"></i>
                <span id="btn-text">Send Reset Link</span>
            </button>
        </form>

        <!-- Footer -->
        <div class="fp-footer">
            <a href="login.php" class="back-link">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Back to Login
            </a>
        </div>

    </div><!-- /.fp-card -->

    <div class="security-badge" aria-hidden="true">
        <i class="fa-solid fa-shield-halved"></i>
        Links expire in 15 minutes &bull; One-time use only
    </div>
</div>

<script>
// Show spinner on submit
document.getElementById('fp-form').addEventListener('submit', function (e) {
    const email = document.getElementById('email').value.trim();
    if (!email) return;

    const btn    = document.getElementById('submit-btn');
    const spinner= document.getElementById('spinner');
    const icon   = document.getElementById('btn-icon');
    const text   = document.getElementById('btn-text');

    btn.disabled        = true;
    spinner.style.display = 'block';
    icon.style.display  = 'none';
    text.textContent    = 'Sending…';
});
</script>

</body>
</html>