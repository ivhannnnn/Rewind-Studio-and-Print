<?php
session_start();
include("db.php");

/* ── Auth guard ───────────────────────────────────────────────────────── */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id    = (int) $_SESSION['user']['id'];
$booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

/* ── Validate booking belongs to this user ────────────────────────────── */
if ($booking_id <= 0) {
    header("Location: booking_status.php");
    exit();
}

$ownership = $conn->prepare("
    SELECT id FROM bookings
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$ownership->bind_param("ii", $booking_id, $user_id);
$ownership->execute();
if ($ownership->get_result()->num_rows === 0) {
    header("Location: booking_status.php");
    exit();
}

/* ── Already reviewed? ────────────────────────────────────────────────── */
$check = $conn->prepare("
    SELECT id FROM reviews
    WHERE booking_id = ? AND user_id = ?
    LIMIT 1
");
$check->bind_param("ii", $booking_id, $user_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header("Location: booking_status.php");
    exit();
}

/* ── CSRF token ───────────────────────────────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors  = [];
$success = false;

/* ── Handle submission ────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {

    /* CSRF check */
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid request. Please try again.";
    }

    $rating   = filter_input(INPUT_POST, 'rating',   FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1, 'max_range' => 5]]);
    $feedback = trim($_POST['feedback'] ?? '');

    if (!$rating) {
        $errors[] = "Please choose a rating between 1 and 5.";
    }
    if (strlen($feedback) < 10) {
        $errors[] = "Feedback must be at least 10 characters.";
    }
    if (strlen($feedback) > 1000) {
        $errors[] = "Feedback must be under 1000 characters.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO reviews (user_id, booking_id, rating, feedback)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $user_id, $booking_id, $rating, $feedback);

        if ($stmt->execute()) {
            /* Rotate CSRF token after successful use */
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: booking_status.php?reviewed=1");
            exit();
        }

        $errors[] = "Something went wrong. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Experience — Rewind Studio</title>
    <link rel="stylesheet" href="ratev3.css">
</head>
<body>

<!-- ═══ HEADER ════════════════════════════════════════════════════════════ -->
<header>
    <div class="logo">
        <img src="486603552_1072841941528975_1520142067500954297_n.jpg"
             alt="Rewind Studio logo">
        <span>Rewind Studio &amp; Prints</span>
    </div>

    <nav class="nav" aria-label="Main navigation">
        <a href="dashboard.php">Dashboard</a>
        <a href="services.php">Services</a>
        <a href="booking_status.php" class="active">My Bookings</a>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </nav>
</header>

<!-- ═══ MAIN ══════════════════════════════════════════════════════════════ -->
<main>
    <section class="rate-section" aria-labelledby="rate-heading">

        <!-- Decorative accent ring -->
        <div class="accent-ring" aria-hidden="true"></div>

        <header class="rate-header">
            <div class="rate-icon" aria-hidden="true">✦</div>
            <h1 id="rate-heading">Rate Your Experience</h1>
            <p class="rate-subtitle">Your feedback helps us craft better moments for everyone.</p>
        </header>

        <!-- Error messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert" aria-live="polite">
                <span class="alert-icon" aria-hidden="true">⚠</span>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <!-- ── Star picker ─────────────────────────────────────────── -->
            <fieldset class="field-group" aria-labelledby="rating-label">
                <legend id="rating-label" class="field-label">Your Rating</legend>

                <div class="star-picker" role="radiogroup" aria-label="Select a star rating">
                    <?php
                    $labels = [5 => 'Excellent', 4 => 'Very Good', 3 => 'Good',
                               2 => 'Fair',      1 => 'Poor'];
                    $prev_rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
                    foreach ($labels as $val => $label):
                        $checked = ($prev_rating === $val) ? 'checked' : '';
                    ?>
                    <label class="star-option" title="<?= $label ?>">
                        <input type="radio" name="rating" value="<?= $val ?>"
                               <?= $checked ?> required>
                        <span class="star" aria-label="<?= $val ?> star<?= $val > 1 ? 's' : '' ?> — <?= $label ?>">★</span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p class="star-hint" aria-live="polite" id="rating-desc">
                    Click a star to set your rating
                </p>
            </fieldset>

            <!-- ── Feedback ────────────────────────────────────────────── -->
            <div class="field-group">
                <label for="feedback" class="field-label">Your Feedback</label>
                <textarea
                    id="feedback"
                    name="feedback"
                    placeholder="Tell us about your experience — what stood out, what we can improve…"
                    minlength="10"
                    maxlength="1000"
                    required
                    aria-describedby="feedback-count"
                ><?= isset($_POST['feedback'])
                    ? htmlspecialchars($_POST['feedback'], ENT_QUOTES, 'UTF-8')
                    : '' ?></textarea>
                <span class="char-count" id="feedback-count" aria-live="polite">
                    0 / 1000
                </span>
            </div>

            <!-- ── Submit ──────────────────────────────────────────────── -->
            <button type="submit" name="submit_review" class="btn-submit">
                Submit Review
            </button>
        </form>

    </section>
</main>

<!-- ═══ FOOTER ════════════════════════════════════════════════════════════ -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3>Studio</h3>
            <h2>Rewind Studio &amp; Prints</h2>
            <p>Capturing authentic moments with a cinematic eye.</p>
        </div>
        <div class="footer-section center">
            <h3>Connect</h3>
            <div class="social-icons">
                <a href="#" aria-label="Facebook">f</a>
                <a href="#" aria-label="Instagram">in</a>
            </div>
        </div>
        <div class="footer-section">
            <h3>Navigation</h3>
            <p><a href="services.php" style="color:var(--muted)">Services</a></p>
            <p><a href="booking_status.php" style="color:var(--muted)">My Bookings</a></p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Rewind Studio &amp; Prints. All rights reserved.
    </div>
</footer>

<script>
/* ── Star picker interaction ──────────────────────────────────────────── */
const starLabels = {5:'Excellent',4:'Very Good',3:'Good',2:'Fair',1:'Poor'};
const stars      = document.querySelectorAll('.star-option');
const ratingDesc = document.getElementById('rating-desc');

stars.forEach(label => {
    const radio = label.querySelector('input');
    const star  = label.querySelector('.star');

    radio.addEventListener('change', () => updateStars());

    label.addEventListener('mouseenter', () => highlightUpTo(parseInt(radio.value)));
    label.addEventListener('mouseleave', () => updateStars());
});

function highlightUpTo(val) {
    stars.forEach(l => {
        const s = l.querySelector('.star');
        const v = parseInt(l.querySelector('input').value);
        s.classList.toggle('hovered', v <= val);
    });
    ratingDesc.textContent = `${val} star${val > 1 ? 's' : ''} — ${starLabels[val]}`;
}

function updateStars() {
    const checked = document.querySelector('.star-option input:checked');
    const val     = checked ? parseInt(checked.value) : 0;

    stars.forEach(l => {
        const s = l.querySelector('.star');
        const v = parseInt(l.querySelector('input').value);
        s.classList.remove('hovered');
        s.classList.toggle('active', v <= val);
    });

    ratingDesc.textContent = val
        ? `${val} star${val > 1 ? 's' : ''} — ${starLabels[val]}`
        : 'Click a star to set your rating';
}

updateStars();

/* ── Character counter ────────────────────────────────────────────────── */
const textarea  = document.getElementById('feedback');
const charCount = document.getElementById('feedback-count');

textarea.addEventListener('input', () => {
    const len = textarea.value.length;
    charCount.textContent = `${len} / 1000`;
    charCount.classList.toggle('near-limit', len >= 900);
});
</script>

</body>
</html>