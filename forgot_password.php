<?php
/**
 * MUKIL TECH - Forgot Password
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
$businessPhone = getSetting('phone', '077 479 5253 / 075 531 6840');
$error = '';
$success = false;
$resetLink = '';
$otpCode = '';
$maskedEmail = '';
$mailSent = false;

// Ensure password_resets table exists
try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS `password_resets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `token` VARCHAR(100) NOT NULL,
        `otp` VARCHAR(10) NOT NULL,
        `expires_at` DATETIME NOT NULL,
        `used` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`token`),
        INDEX (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    error_log('[mukiltech] Failed to ensure password_resets table: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $identifier = trim($_POST['identifier'] ?? '');

    if (empty($identifier)) {
        $error = 'Please enter your username or registered email address.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE (LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)) AND LOWER(status) = 'active' LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user) {
                // Invalidate any existing unused reset tokens for this user
                $db->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")->execute([$user['user_id']]);

                // Generate secure token and 6-digit OTP
                $token = bin2hex(random_bytes(32));
                $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                $userEmail = trim($user['email'] ?? '');
                if (empty($userEmail)) {
                    $userEmail = $user['username'] . '@mukiltech.local';
                }

                $ins = $db->prepare("INSERT INTO password_resets (user_id, email, token, otp, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
                $ins->execute([$user['user_id'], $userEmail, $token, $otp]);

                $success = true;
                $resetLink = baseUrl('reset_password.php?token=' . urlencode($token));
                $otpCode = $otp;

                // Mask email for display (e.g., ad***@mukiltech.com)
                if (filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                    $parts = explode('@', $userEmail);
                    $namePart = $parts[0];
                    $domainPart = $parts[1] ?? '';
                    $visibleLen = min(2, strlen($namePart));
                    $maskedEmail = substr($namePart, 0, $visibleLen) . str_repeat('*', max(3, strlen($namePart) - $visibleLen)) . '@' . $domainPart;
                } else {
                    $maskedEmail = 'your registered account';
                }

                // Attempt to send email via PHP mail()
                if (filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                    $subject = "Password Reset Request - " . $businessName;
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                    $fullResetUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $resetLink;

                    $message = "Hello " . ($user['full_name'] ?? $user['username']) . ",\n\n";
                    $message .= "A password reset request was received for your account at " . $businessName . ".\n\n";
                    $message .= "Your Verification Code: " . $otp . "\n";
                    $message .= "Direct Reset Link:\n" . $fullResetUrl . "\n\n";
                    $message .= "This link and code will expire in 1 hour.\n";
                    $message .= "If you did not request this reset, please ignore this email.\n\n";
                    $message .= "Regards,\n" . $businessName . "\nPhone: " . $businessPhone;

                    $headers = "From: no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'mukiltech.com') . "\r\n" .
                               "Reply-To: " . getSetting('email', 'mukiltech7@gmail.com') . "\r\n" .
                               "X-Mailer: PHP/" . phpversion();

                    // Suppress warning if mail server is not configured in php.ini
                    $mailSent = @mail($userEmail, $subject, $message, $headers);
                }
            } else {
                // If user not found, give a neutral message to prevent username enumeration,
                // but inform the user to contact the shop admin if needed
                $error = 'No active account found with that username or email. Please check and try again.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred while processing your request. Please try again or contact the administrator.';
            error_log('[mukiltech] Forgot password error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - <?= e($businessName) ?></title>
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
        .forgot-container {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            width: 100%;
            max-width: 440px;
            transition: background-color 0.2s, border-color 0.2s;
            box-sizing: border-box;
        }
        .forgot-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .forgot-logo img {
            max-width: 130px;
            height: auto;
        }
        .forgot-title {
            text-align: center;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1.45rem;
            font-weight: 700;
        }
        .forgot-subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.88rem;
            margin-bottom: 1.5rem;
            line-height: 1.45;
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
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .reset-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.25rem;
            margin-top: 1rem;
            margin-bottom: 1.25rem;
            text-align: center;
        }
        .otp-display {
            display: inline-block;
            font-family: monospace;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: 4px;
            color: #0284c7;
            background: #e0f2fe;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            margin: 0.5rem 0;
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
        html.dark-theme .forgot-container {
            background: #1e293b;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid #334155;
        }
        html.dark-theme .forgot-title {
            color: #f0f6fc;
        }
        html.dark-theme .forgot-subtitle {
            color: #94a3b8;
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
        html.dark-theme .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-color: rgba(52, 211, 153, 0.3);
        }
        html.dark-theme .reset-box {
            background: #0d1117;
            border-color: #334155;
        }
        html.dark-theme .otp-display {
            background: rgba(14, 165, 233, 0.2);
            color: #38bdf8;
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

<div class="forgot-container">
    <div class="forgot-logo">
        <a href="<?= baseUrl('login.php') ?>">
            <img src="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>" alt="<?= e($businessName) ?> Logo">
        </a>
    </div>

    <h2 class="forgot-title">Reset Your Password</h2>

    <?php if (!$success): ?>
        <p class="forgot-subtitle">
            Enter your username or registered email address, and we'll help you reset your password.
        </p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= baseUrl('forgot_password.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label class="form-label" for="identifier">Username or Email Address</label>
                <input type="text" id="identifier" name="identifier" class="form-control" placeholder="e.g. admin or staff@mukiltech.com" required autofocus value="<?= e($_POST['identifier'] ?? '') ?>">
            </div>

            <button type="submit" class="btn-submit">Generate Reset Link</button>
        </form>

        <div class="action-links">
            <a href="<?= baseUrl('login.php') ?>">&larr; Back to Sign In</a>
            <a href="<?= baseUrl('home.php') ?>">Home Page</a>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <strong>Password Reset Request Created!</strong>
            <p style="margin:0.4rem 0 0 0;font-size:0.85rem;">
                A reset token has been generated for <strong><?= e($maskedEmail) ?></strong>.
            </p>
        </div>

        <?php if ($mailSent): ?>
            <p style="font-size:0.88rem;color:#475569;text-align:center;line-height:1.5;">
                We've dispatched an email containing your reset instructions and verification code. Please check your inbox.
            </p>
        <?php endif; ?>

        <div class="reset-box">
            <div style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;font-weight:700;">
                Verification Security Code
            </div>
            <div class="otp-display"><?= e($otpCode) ?></div>
            <div style="font-size:0.75rem;color:#94a3b8;margin-bottom:1rem;">
                Valid for 1 hour
            </div>

            <a href="<?= e($resetLink) ?>" class="btn-submit" style="display:block;text-align:center;text-decoration:none;box-sizing:border-box;">
                Proceed to Reset Password &rarr;
            </a>
        </div>

        <p style="font-size:0.8rem;color:#64748b;text-align:center;line-height:1.4;">
            Need help? Contact system support at <strong><?= e($businessPhone) ?></strong>.
        </p>

        <div class="action-links" style="justify-content:center;">
            <a href="<?= baseUrl('login.php') ?>">&larr; Return to Sign In</a>
        </div>
    <?php endif; ?>

    <div class="footer-note">
        &copy; <?= date('Y') ?> <?= e($businessName) ?> &middot; System Security
    </div>
</div>

</body>
</html>
