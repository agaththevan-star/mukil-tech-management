<?php
// Temporary WAMP diagnostic. Delete this file after setup if the system is exposed publicly.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireRole('admin');

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>MUKIL TECH - WAMP Check</title>
<style>
body{font-family:Arial,sans-serif;background:#f1f5f9;padding:30px;color:#0f172a}
.card{max-width:760px;margin:auto;background:#fff;padding:28px;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.ok{color:#15803d}.bad{color:#b91c1c}code{background:#f1f5f9;padding:3px 6px;border-radius:5px}
</style></head><body><div class="card">
<h1>MUKIL TECH WAMP Check</h1>
<p><strong>PHP:</strong> <?= htmlspecialchars(PHP_VERSION) ?></p>
<p><strong>PDO:</strong> <?= extension_loaded('pdo') ? '<span class="ok">OK</span>' : '<span class="bad">Missing</span>' ?></p>
<p><strong>PDO MySQL:</strong> <?= extension_loaded('pdo_mysql') ? '<span class="ok">OK</span>' : '<span class="bad">Missing</span>' ?></p>
<?php
try {
    $db = getDB();
    $count = (int)$db->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    echo '<p><strong>MySQL:</strong> <span class="ok">Connected</span></p>';
    echo '<p><strong>Database:</strong> <code>mukiltech</code></p>';
    echo '<p><strong>Settings rows:</strong> ' . $count . '</p>';
} catch (Throwable $e) {
    error_log('[mukiltech][wamp-check] DB connection error: ' . $e->getMessage());
    echo '<p><strong>MySQL:</strong> <span class="bad">Connection failed</span></p>';
    echo '<p class="bad">Unable to establish a database connection. Check server logs for details.</p>';
}
?>
<p><a href="index.php">Open MUKIL TECH</a></p>
</div></body></html>
