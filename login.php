<?php
/**
 * MUKIL TECH - Login Page
 */

define('SKIP_AUTO_LOGIN', true);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . baseUrl('admin/dashboard.php'));
    exit;
}

$error = '';
$success = '';

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = 'Password reset successfully! Please sign in with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic rate limiting or CSRF could go here, but let's keep it simple
    // Simple session based rate limiter
    if (empty($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_last_attempt'] = 0;
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // If too many attempts within short time, throttle
    if ($_SESSION['login_attempts'] >= 6 && (time() - ($_SESSION['login_last_attempt'] ?? 0)) < 300) {
        $error = 'Too many login attempts. Please try again later.';
    }
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful, set session variables
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            // reset rate limiter on success
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_last_attempt'] = time();
            
            // Redirect to dashboard
            header('Location: ' . baseUrl('admin/dashboard.php'));
            exit;
        } else {
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['login_last_attempt'] = time();
            $error = 'Invalid username or password, or account inactive.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - MUKIL TECH</title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.2s;
        }
        .login-container {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            width: 100%;
            max-width: 400px;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo img {
            max-width: 150px;
            height: auto;
        }
        .login-title {
            text-align: center;
            color: #1e293b;
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #475569;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
            background: #ffffff;
            color: #0f172a;
            transition: border-color 0.2s, background-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn-login {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.2s;
        }
        .btn-login:hover {
            background-color: #1d4ed8;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            text-align: center;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            text-align: center;
            border: 1px solid #a7f3d0;
        }
        .forgot-link {
            font-size: 0.82rem;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .forgot-link:hover {
            text-decoration: underline;
            color: #1d4ed8;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        /* ===== Dark Theme ===== */
        html.dark-theme body {
            background-color: #090e17;
        }
        html.dark-theme .login-container {
            background: #1e293b;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid #334155;
        }
        html.dark-theme .login-title {
            color: #f0f6fc;
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
        html.dark-theme .forgot-link {
            color: #38bdf8;
        }
        html.dark-theme .forgot-link:hover {
            color: #7dd3fc;
        }
        html.dark-theme .login-footer {
            color: #8b949e;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-logo">
        <a href="<?= baseUrl('home.php') ?>">
            <img src="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>" alt="MUKIL TECH Logo">
        </a>
    </div>
    
    <h2 class="login-title">Sign In</h2>
    
    <?php if ($success): ?>
        <div class="alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="<?= baseUrl('login.php') ?>">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" required autofocus>
        </div>
        
        <div class="form-group">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                <label class="form-label" for="password" style="margin-bottom:0;">Password</label>
                <a href="<?= baseUrl('forgot_password.php') ?>" class="forgot-link">Forgot password?</a>
            </div>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        
        <button type="submit" class="btn-login">Log In</button>
    </form>
    
    <div class="login-footer">
        <div><a href="<?= baseUrl('home.php') ?>" style="color:#64748b;text-decoration:none;font-size:0.82rem;">&larr; Back to Home Page</a></div>
        <div style="margin-top:0.5rem;">&copy; <?= date('Y') ?> MUKIL TECH</div>
    </div>
</div>

</body>
</html>
