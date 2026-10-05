<?php
/**
 * MUKIL TECH - Reset Password Page
 */

define('SKIP_AUTO_LOGIN', true);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . baseUrl('admin/dashboard.php'));
    exit;
}

$businessName = getSetting('business_name', 'MUKIL TECH');
$error = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$resetRecord = null;
$user = null;

if (empty($token)) {
    $error = 'Invalid or missing password reset token. Please request a new link.';
} else {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT pr.*, u.username, u.full_name, u.email as user_email, 
                              (pr.expires_at < NOW()) AS is_expired 
                              FROM password_resets pr 
                              JOIN users u ON pr.user_id = u.user_id 
                              WHERE pr.token = ? LIMIT 1");
        $stmt->execute([$token]);
        $resetRecord = $stmt->fetch();

        if (!$resetRecord) {
            $error = 'This reset link does not exist. Please request a new password reset.';
        } elseif ((int)$resetRecord['used'] === 1) {
            $error = 'This password reset link has already been used. Please request a new one if needed.';
        } elseif ((int)$resetRecord['is_expired'] === 1) {
            $error = 'This password reset link has expired (links are valid for 1 hour). Please request a new one.';
        } else {
            $user = $resetRecord;
        }
    } catch (Exception $e) {
        $error = 'A database error occurred while verifying your request.';
        error_log('[mukiltech] Reset password check error: ' . $e->getMessage());
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    verifyCsrfToken();

    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in both password fields.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters in length.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match. Please verify and try again.';
    } else {
        try {
            $db = getDB();
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update user password
            $upUser = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $upUser->execute([$hashed, (int)$user['user_id']]);

            // Mark token as used
            $upReset = $db->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $upReset->execute([(int)$resetRecord['id']]);

            // Redirect to login with success indicator
            flashMessage('Your password has been successfully reset. Please sign in with your new password.', 'success');
            header('Location: ' . baseUrl('login.php?reset=success'));
            exit;
        } catch (Exception $e) {
            $error = 'Failed to update password. Please try again.';
            error_log('[mukiltech] Password update error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password - <?= e($businessName) ?></title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>">
    <script>
        // Apply theme immediately to prevent FOUC
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-theme');
        }
    </script>
    <style>
        body {
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1.25rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            transition: background-color 0.2s;
            box-sizing: border-box;
        }
        .reset-container {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            width: 100%;
            max-width: 440px;
            transition: background-color 0.2s, border-color 0.2s;
            box-sizing: border-box;
        }
        .reset-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .reset-logo img {
            max-width: 130px;
            height: auto;
        }
        .reset-title {
            text-align: center;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1.45rem;
            font-weight: 700;
        }
        .reset-subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.88rem;
            margin-bottom: 1.5rem;
            line-height: 1.45;
        }
        .user-badge {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .user-badge .icon {
            font-size: 1.4rem;
        }
        .user-badge .details {
            font-size: 0.85rem;
            color: #0369a1;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #334155;
            font-weight: 600;
            font-size: 0.88rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 0.85rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.95rem;
            box-sizing: border-box;
            background: #ffffff;
            color: #0f172a;
            transition: border-color 0.2s, background-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }
        .btn-submit {
            background-color: #0284c7;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #0369a1;
        }
        .alert {
            padding: 0.85rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .action-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.85rem;
        }
        .action-links a {
            color: #0284c7;
            text-decoration: none;
            font-weight: 600;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
        .footer-note {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.78rem;
            color: #94a3b8;
        }

        /* ===== Dark Theme ===== */
        html.dark-theme body {
            background-color: #090e17;
        }
        html.dark-theme .reset-container {
            background: #1e293b;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid #334155;
        }
        html.dark-theme .reset-title {
            color: #f0f6fc;
        }
        html.dark-theme .reset-subtitle {
            color: #94a3b8;
        }
        html.dark-theme .user-badge {
            background: #0d1117;
            border-color: #334155;
        }
        html.dark-theme .user-badge .details {
            color: #38bdf8;
        }
        html.dark-theme .form-label {
            color: #cbd5e1;
        }
        html.dark-theme .form-control {
            background: #0d1117;
            color: #f0f6fc;
            border-color: #484f58;
        }
        html.dark-theme .form-control:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }
        html.dark-theme .alert-danger {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border-color: rgba(248, 113, 113, 0.3);
        }
        html.dark-theme .action-links {
            border-top-color: #334155;
        }
        html.dark-theme .action-links a {
            color: #38bdf8;
        }
        html.dark-theme .footer-note {
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="reset-container">
    <div class="reset-logo">
        <a href="<?= baseUrl('login.php') ?>">
            <img src="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>" alt="<?= e($businessName) ?> Logo">
        </a>
    </div>

    <h2 class="reset-title">Create New Password</h2>

    <?php if ($user): ?>
        <p class="reset-subtitle">
            Choose a new secure password for your account.
        </p>

        <div class="user-badge">
            <span class="icon">&#128100;</span>
            <div class="details">
                Resetting password for: <strong><?= e($user['full_name']) ?></strong> (<code><?= e($user['username']) ?></code>)
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="resetPasswordForm" onsubmit="return validatePasswords();">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="form-group">
                <label class="form-label" for="password">New Password</label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required autofocus minlength="6">
                    <button type="button" onclick="togglePassVisibility('password', this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;font-size:1rem;" title="Show/Hide Password">&#128065;</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <div style="position:relative;">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required minlength="6">
                    <button type="button" onclick="togglePassVisibility('confirm_password', this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;font-size:1rem;" title="Show/Hide Password">&#128065;</button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Save New Password</button>
        </form>

        <div class="action-links">
            <a href="<?= baseUrl('login.php') ?>">&larr; Cancel and Sign In</a>
            <a href="<?= baseUrl('forgot_password.php') ?>">Request New Link</a>
        </div>

        <script>
        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.style.color = '#0284c7';
            } else {
                input.type = 'password';
                btn.style.color = '#64748b';
            }
        }

        function validatePasswords() {
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;
            if (p1.length < 6) {
                alert('Password must be at least 6 characters.');
                return false;
            }
            if (p1 !== p2) {
                alert('Passwords do not match. Please re-type your confirm password.');
                return false;
            }
            return true;
        }
        </script>
    <?php else: ?>
        <div class="alert alert-danger" style="margin-top:1rem;">
            <strong>Link Error:</strong><br>
            <?= e($error) ?>
        </div>

        <div style="text-align:center;margin-top:1.5rem;">
            <a href="<?= baseUrl('forgot_password.php') ?>" class="btn-submit" style="display:inline-block;text-decoration:none;box-sizing:border-box;">
                Request New Password Reset &rarr;
            </a>
        </div>

        <div class="action-links" style="justify-content:center;margin-top:1.5rem;">
            <a href="<?= baseUrl('login.php') ?>">&larr; Back to Sign In</a>
        </div>
    <?php endif; ?>

    <div class="footer-note">
        &copy; <?= date('Y') ?> <?= e($businessName) ?> &middot; System Security
    </div>
</div>

</body>
</html>
