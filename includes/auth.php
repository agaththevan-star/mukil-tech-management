<?php
/**
 * MUKIL TECH - Authentication & Authorization Helper (Direct Access Mode)
 */

// WAMP/Apache session configuration.
// Use session cookies (no persistent cookie) and tightened flags.
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0, // session cookie (expires on browser close)
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_start();
}

// Auto-login removed
// Switch-role via GET removed

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is logged in
 * @return bool
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

/**
 * Enforce login for protected pages (always authenticated)
 */
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . baseUrl('login.php'));
        exit;
    }
}

/**
 * Get the currently logged-in user array
 * @return array
 */
function currentUser() {
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
    ];
}

/**
 * Check if the logged-in user has one of the allowed roles
 * @param array|string $roles
 * @return bool
 */
function hasRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return isLoggedIn() && in_array($_SESSION['role'] ?? '', $roles, true);
}

/**
 * Enforce role requirement, show 403 Forbidden if not permitted
 * @param array|string $roles
 */
function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        http_response_code(403);
        require_once __DIR__ . '/functions.php';
        die("<!DOCTYPE html>
        <html lang='en'>
        <head><title>Access Denied - MUKIL TECH</title><link rel='stylesheet' href='" . baseUrl('assets/css/style.css') . "'></head>
        <body style='display:flex;align-items:center;justify-content:center;height:100vh;background:#f1f5f9;margin:0;'>
            <div style='background:#fff;padding:40px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.05);text-align:center;max-width:480px;'>
                <div style='font-size:50px;color:#ef4444;margin-bottom:15px;'>&#9888;</div>
                <h2 style='color:#0f172a;margin:0 0 10px 0;'>Role Restricted</h2>
                <p style='color:#64748b;margin-bottom:25px;'>Your account does not have permission to access this module.</p>
                <div style='display:flex;gap:10px;justify-content:center;'>
                    <a href='" . baseUrl('admin/dashboard.php') . "' class='btn btn-outline'>Back to Dashboard</a>
                </div>
            </div>
        </body>
        </html>");
    }
}

/**
 * Generate or get CSRF token
 * @return string
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST
 * @param bool $terminateOnError Whether to halt on verification failure
 * @return bool
 */
function verifyCsrfToken($terminateOnError = true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $validBySession = (is_string($token) && is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token));

        if ($validBySession) {
            if (empty($_SESSION['csrf_token']) && !empty($token)) {
                $_SESSION['csrf_token'] = $token;
            }
            return true;
        }

        if ($terminateOnError) {
            http_response_code(403);
            require_once __DIR__ . '/functions.php';
            die("<!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Security Notice - MUKIL TECH</title>
                <link rel='stylesheet' href='" . baseUrl('assets/css/style.css') . "'>
            </head>
            <body style='display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0f172a;margin:0;font-family:system-ui,-apple-system,sans-serif;padding:1.5rem;'>
                <div style='background:#ffffff;padding:2.5rem;border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.4);text-align:center;max-width:460px;width:100%;'>
                    <div style='font-size:46px;color:#f59e0b;margin-bottom:12px;'>&#9888;</div>
                    <h3 style='color:#0f172a;margin:0 0 10px 0;font-size:1.3rem;font-weight:700;'>Session Expired</h3>
                    <p style='color:#64748b;font-size:0.92rem;line-height:1.55;margin-bottom:24px;'>
                        Your security verification token has refreshed. Please go back or refresh the page to continue.
                    </p>
                    <div style='display:flex;gap:10px;justify-content:center;'>
                        <a href='javascript:history.back()' class='btn btn-outline'>Go Back</a>
                        <a href='" . baseUrl('admin/dashboard.php') . "' class='btn btn-primary'>Go to Dashboard</a>
                    </div>
                </div>
            </body>
            </html>");
        }
        return false;
    }
    return true;
}
