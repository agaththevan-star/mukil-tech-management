<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/auth.php';
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$_SESSION = [];
check(!hasRole('admin'), 'Anonymous users must not be administrators.');
check(currentUser()['id'] === null, 'Anonymous identity must be empty.');
$_SESSION['role'] = 'admin';
check(!hasRole('admin'), 'A role alone must not authenticate a user.');
$_SESSION['user_id'] = 1;
check(hasRole('admin'), 'Authenticated role must be recognized.');
check(!hasRole('cashier'), 'Role restrictions must hold.');
$_SERVER['REQUEST_METHOD'] = 'POST';
$token = csrfToken();
$_POST['csrf_token'] = $token;
check(verifyCsrfToken(false), 'Current session token must pass.');
$_POST['csrf_token'] = ['malformed'];
check(!verifyCsrfToken(false), 'Array tokens must be rejected without crashing.');
$_SESSION = [];
$_POST['csrf_token'] = $token;
check(!verifyCsrfToken(false), 'Another session token must fail.');
$payload = time() . '.' . bin2hex(random_bytes(16));
$_POST['csrf_token'] = base64_encode($payload . '.' . hash_hmac('sha256', $payload, 'mukil_tech_kilinochchi_secure_csrf_key_2026'));
check(!verifyCsrfToken(false), 'Legacy publicly forgeable tokens must fail.');
$_SESSION['login_attempts'] = 6;
$_SESSION['login_last_attempt'] = time();
$_POST = ['csrf_token' => csrfToken(), 'username' => 'admin', 'password' => 'wrong'];
$_SERVER['SCRIPT_NAME'] = '/login.php';
ob_start();
require __DIR__ . '/../login.php';
$output = ob_get_clean();
check(strpos($output, 'Too many login attempts') !== false, 'Throttled login must stop before database authentication.');
session_destroy();
echo "Security regression checks passed.\n";
