<?php
/**
 * MUKIL TECH - Billing, POS, Inventory & PC Repair System
 * Database Connection Configuration (PDO MySQL)
 * 
 * Technology: PHP 8+, MySQL
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'mukiltech');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns shared PDO instance connected to MySQL
 * @return PDO
 */
function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Never expose raw DB errors in production. Log details and show a generic message.
        error_log('[mukiltech][DB] Connection error: ' . $e->getMessage());
        $debug = getenv('APP_DEBUG') === 'true' || getenv('APP_ENV') === 'development';
        if ($debug) {
            // In local/dev show the original exception for debugging
            die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border:1px solid #f87171;border-radius:8px;max-width:700px;margin:50px auto;'>
                <h2 style='margin-top:0'>MUKIL TECH - MySQL Database Connection Error</h2>
                <p>Could not connect to MySQL database <strong>" . htmlspecialchars(DB_NAME) . "</strong>.</p>
                <p><strong>MySQL Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <hr style='border:0;border-top:1px solid #fca5a5;margin:15px 0'>
                <p><strong>Setup Instructions:</strong></p>
                <ol>
                    <li>Ensure MySQL / MariaDB service is running.</li>
                    <li>Verify database <code>mukiltech</code> exists and <code>database.sql</code> is imported.</li>
                    <li>Verify DB credentials in <code>config/database.php</code>.</li>
                </ol>
            </div>");
        }

        // Generic message for non-debug environments
        die("<div style='font-family:sans-serif;padding:30px;background:#fff7ed;color:#7c2d12;border:1px solid #f59e0b;border-radius:8px;max-width:700px;margin:50px auto;'>
            <h2 style='margin-top:0'>MUKIL TECH</h2>
            <p>Unable to connect to the application database. Please contact the system administrator.</p>
        </div>");
    }
}
