    <?php
/**
 * google_auth.php — Google OAuth 2.0 Callback Handler
 *
 * Flow:
 *  1. Login page calls this with ?action=login  → redirects user to Google
 *  2. Google redirects back here with ?code=... → exchanges code for token
 *  3. Fetches user info from Google
 *  4. Finds or creates user in DB → sets session → redirects to app
 *
 * No external library required — uses PHP cURL only.
 */

// ── Session hardening ──────────────────────────────────────────────────────
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
    // 'secure' => true,  // uncomment on HTTPS
]);
session_start();

require_once 'db.php';          // provides $conn (mysqli)
require_once 'google_config.php';

// ── Helper: cURL POST ──────────────────────────────────────────────────────
function curl_post(string $url, array $fields): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,   // always verify SSL in production
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new RuntimeException("cURL error: $error");
    }

    return json_decode($response, true) ?? [];
}

// ── Helper: cURL GET with Bearer token ────────────────────────────────────
function curl_get_bearer(string $url, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new RuntimeException("cURL error: $error");
    }

    return json_decode($response, true) ?? [];
}

// ── Helper: abort with message ─────────────────────────────────────────────
function abort(string $message): never
{
    $_SESSION['login_error'] = $message;
    header('Location: login_v9.php');
    exit();
}

// ── Determine action ───────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

/* ════════════════════════════════════════════════════════════════════════════
   STEP 1 — Redirect user to Google's consent screen
════════════════════════════════════════════════════════════════════════════ */
if ($action === 'login') {

    // Generate CSRF state token and store in session
    $state = bin2hex(random_bytes(32));
    $_SESSION['google_oauth_state'] = $state;

    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'online',
        'prompt'        => 'select_account',   // always show account picker
        'state'         => $state,
    ];

    header('Location: ' . GOOGLE_AUTH_URL . '?' . http_build_query($params));
    exit();
}

/* ════════════════════════════════════════════════════════════════════════════
   STEP 2 — Google redirects back with ?code=... (the callback)
════════════════════════════════════════════════════════════════════════════ */

// ── 2a. Check for user-denied or OAuth errors ──────────────────────────────
if (isset($_GET['error'])) {
    abort('Google login was cancelled or denied. Please try again.');
}

// ── 2b. Verify CSRF state parameter ───────────────────────────────────────
if (
    empty($_GET['state'])                          ||
    empty($_SESSION['google_oauth_state'])         ||
    !hash_equals($_SESSION['google_oauth_state'], $_GET['state'])
) {
    unset($_SESSION['google_oauth_state']);
    abort('Security check failed (invalid state). Please try again.');
}
unset($_SESSION['google_oauth_state']);

// ── 2c. Authorization code must be present ────────────────────────────────
if (empty($_GET['code'])) {
    abort('Google did not return an authorization code. Please try again.');
}

$auth_code = $_GET['code'];

try {
    /* ── 2d. Exchange auth code for access token ────────────────────────── */
    $token_data = curl_post(GOOGLE_TOKEN_URL, [
        'code'          => $auth_code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);

    if (empty($token_data['access_token'])) {
        $err = $token_data['error_description'] ?? 'Unknown token error';
        throw new RuntimeException("Token exchange failed: $err");
    }

    $access_token = $token_data['access_token'];

    /* ── 2e. Fetch user info from Google ────────────────────────────────── */
    $google_user = curl_get_bearer(GOOGLE_USERINFO_URL, $access_token);

    if (empty($google_user['email'])) {
        throw new RuntimeException('Could not retrieve email from Google.');
    }

    // Sanitise fields coming from Google
    $google_id   = $google_user['sub']            ?? '';
    $email       = strtolower(trim($google_user['email']));
    $full_name   = trim($google_user['name']      ?? '');
    $picture     = $google_user['picture']        ?? '';
    $verified    = (bool) ($google_user['email_verified'] ?? false);

    if (!$verified) {
        abort('Your Google account email is not verified. Please verify it first.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        abort('Invalid email received from Google.');
    }

    /* ── 2f. Find or create user in database ────────────────────────────── */
    /*
     * Expected users table columns (add google_id + avatar if missing):
     *
     *   ALTER TABLE users
     *     ADD COLUMN google_id  VARCHAR(50)  DEFAULT NULL,
     *     ADD COLUMN avatar     VARCHAR(500) DEFAULT NULL,
     *     ADD UNIQUE INDEX idx_google_id (google_id);
     *
     * The query below tries to match on google_id first (returning users),
     * then falls back to email (existing account linking).
     */

    $user = null;

    // 1. Look up by google_id
    $stmt = $conn->prepare(
        "SELECT id, full_name, username, email, avatar
         FROM users
         WHERE google_id = ?
         LIMIT 1"
    );
    $stmt->bind_param('s', $google_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // 2. Fall back to email match (link existing account)
    if (!$user) {
        $stmt = $conn->prepare(
            "SELECT id, full_name, username, email, avatar
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Link google_id and update avatar on the existing account
            $upd = $conn->prepare(
                "UPDATE users
                 SET google_id = ?, avatar = ?
                 WHERE id = ?"
            );
            $upd->bind_param('ssi', $google_id, $picture, $user['id']);
            $upd->execute();
            $upd->close();
            $user['avatar'] = $picture;
        }
    }

    // 3. Create new user if neither matched
    if (!$user) {
        // Generate a unique username from the email prefix
        $base_username = preg_replace('/[^a-z0-9_]/', '', strtolower(
            substr($email, 0, strpos($email, '@'))
        ));
        $base_username = $base_username ?: 'user';

        // Ensure uniqueness — append random suffix if taken
        $username = $base_username;
        $check    = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");

        for ($i = 0; $i < 10; $i++) {
            $check->bind_param('s', $username);
            $check->execute();
            $check->store_result();
            if ($check->num_rows === 0) break;
            $username = $base_username . '_' . rand(100, 9999);
        }
        $check->close();

        // No password for Google-only accounts (NULL)
        $ins = $conn->prepare(
            "INSERT INTO users (full_name, username, email, password, google_id, avatar, created_at)
             VALUES (?, ?, ?, NULL, ?, ?, NOW())"
        );
        $ins->bind_param('sssss', $full_name, $username, $email, $google_id, $picture);

        if (!$ins->execute()) {
            throw new RuntimeException('Failed to create user account: ' . $conn->error);
        }

        $new_id = $ins->insert_id;
        $ins->close();

        $user = [
            'id'        => $new_id,
            'full_name' => $full_name,
            'username'  => $username,
            'email'     => $email,
            'avatar'    => $picture,
        ];
    }

    /* ── 2g. Start authenticated session ────────────────────────────────── */
    session_regenerate_id(true);   // prevent session fixation

    $_SESSION['user'] = [
        'id'        => (int)   $user['id'],
        'full_name' => htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'),
        'username'  => htmlspecialchars($user['username'],  ENT_QUOTES, 'UTF-8'),
        'email'     => htmlspecialchars($user['email'],     ENT_QUOTES, 'UTF-8'),
        'avatar'    => $user['avatar'] ?? '',   // Google profile picture URL
        'auth'      => 'google',
    ];
    $_SESSION['login_time'] = time();

    // Rotate CSRF token after login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: ' . GOOGLE_LOGIN_SUCCESS_URL);
    exit();

} catch (RuntimeException $e) {
    // Log the real error, show a safe message to the user
    error_log('[Google OAuth] ' . $e->getMessage());
    abort('Google login failed. Please try again or use email/password.');
}