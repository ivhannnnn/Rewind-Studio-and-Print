<?php
/**
 * google_config.php — Google OAuth 2.0 Configuration
 *
 * HOW TO SET UP:
 * ─────────────────────────────────────────────────────────────────────────
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project (or select existing)
 * 3. Go to APIs & Services → Credentials
 * 4. Click "Create Credentials" → "OAuth 2.0 Client IDs"
 * 5. Application type: Web application
 * 6. Add Authorized redirect URI:
 *      http://localhost/Rewind_Studio_and_print/google_auth.php
 *    (use your actual domain in production)
 * 7. Copy the Client ID and Client Secret below
 * ─────────────────────────────────────────────────────────────────────────
 */

// ── Your Google OAuth credentials ─────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');

// ── Callback URL — must match exactly what you set in Google Console ───────
// Change this to your actual domain in production
define('GOOGLE_REDIRECT_URI',  'http://localhost/Rewind_Studio_and_print/google_auth.php');

// ── Where to send users after successful Google login ─────────────────────
define('GOOGLE_LOGIN_SUCCESS_URL', 'index.php');

// ── Google OAuth endpoints (do not change) ────────────────────────────────
define('GOOGLE_AUTH_URL',    'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL',   'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL','https://www.googleapis.com/oauth2/v3/userinfo');