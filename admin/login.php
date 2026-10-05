<?php
/**
 * MUKIL TECH - Admin Login Page
 */

$pageTitle = 'Administrator Login';
// Prevent auth.php from auto-creating a demo session for this page
define('SKIP_AUTO_LOGIN', true);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . baseUrl('admin/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please provide both username and password.';
    } else {
        try {
            $db = getDB();
            // Accept any case variant of 'active' stored in DB
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND LOWER(status) = 'active' LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful login - set session values
                // Prevent session fixation
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['email']     = $user['email'] ?? '';

                flashMessage('Welcome back, ' . e($user['full_name']), 'success');
                header('Location: ' . baseUrl('admin/dashboard.php'));
                exit;
            } else {
                $error = 'Invalid credentials or account inactive.';
            }
        } catch (Exception $e) {
            $error = logException($e, 'Login failed due to an internal error.');
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= e(getSetting('business_name', 'MUKIL TECH')) ?></title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body style="background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1.25rem;">
    <div style="width:420px;max-width:100%;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Administrator Login</div>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <span class="alert-icon">&#9888;</span>
                        <span class="alert-content"><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input id="username" name="username" class="form-control" autofocus required value="<?= e($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.4rem;">
                            <label class="form-label" for="password" style="margin-bottom:0;">Password</label>
                            <a href="<?= baseUrl('forgot_password.php') ?>" style="font-size:0.8rem;text-decoration:none;">Forgot password?</a>
                        </div>
                        <input id="password" name="password" type="password" class="form-control" required>
                    </div>

                    <div style="display:flex;gap:0.5rem;justify-content:space-between;align-items:center;margin-top:1rem;">
                        <a href="<?= baseUrl('index.php') ?>" class="btn btn-outline">Back</a>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>

        <div style="text-align:center;margin-top:0.75rem;font-size:0.9rem;color:var(--text-muted);">
            <?= e(getSetting('business_name', 'MUKIL TECH')) ?> &middot; <?= e(getSetting('business_type', 'Computer Sales')) ?>
        </div>
    </div>
</body>
</html>
