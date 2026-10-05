<?php
/**
 * MUKIL TECH - Global Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Return base URL path for the application
 * Automatically adjusts if running in root '/' or a subfolder like '/mukiltech/'
 * @param string $path
 * @return string
 */
function baseUrl($path = '') {
    // Works from WAMP document root, a subfolder such as /mukiltech,
    // or an Apache VirtualHost. Do not depend on the current PHP directory.
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $applicationRoot = realpath(__DIR__ . '/..');

    $base = '';
    if ($documentRoot && $applicationRoot) {
        $doc = str_replace('\\', '/', rtrim($documentRoot, DIRECTORY_SEPARATOR));
        $app = str_replace('\\', '/', rtrim($applicationRoot, DIRECTORY_SEPARATOR));

        if ($app === $doc) {
            $base = '';
        } elseif (stripos($app, $doc . '/') === 0) {
            $base = substr($app, strlen($doc));
        }
    }

    // Fallback for unusual Apache/PHP configurations.
    if ($base === '') {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        foreach (['/admin','/customers','/categories','/suppliers','/products','/sales',
                  '/repairs','/expenses','/payments','/reports','/users','/settings'] as $dir) {
            if ($scriptDir === $dir || substr($scriptDir, -strlen($dir)) === $dir) {
                $scriptDir = substr($scriptDir, 0, -strlen($dir));
                break;
            }
        }
        $base = $scriptDir;
    }

    $base = '/' . trim($base, '/');
    if ($base === '/') {
        $base = '';
    }

    $cleanPath = ltrim((string)$path, '/');
    return $base . ($cleanPath !== '' ? '/' . $cleanPath : '');
}

/**
 * Sanitize string input
 * @param mixed $data
 * @return string
 */
function sanitize($data) {
    if (is_null($data)) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape HTML output
 */
function e($data) {
    return sanitize($data);
}

/**
 * Format currency with configured symbol (Rs.)
 * @param float|int|string $amount
 * @return string
 */
function formatCurrency($amount) {
    $curr = getSetting('currency', 'Rs.');
    return $curr . ' ' . number_format((float)$amount, 2);
}

/**
 * Format date for display
 * @param string|null $date
 * @param bool $includeTime
 * @return string
 */
function formatDate($date, $includeTime = false) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    $ts = strtotime($date);
    if (!$ts) return $date;
    return $includeTime ? date('d M Y, h:i A', $ts) : date('d M Y', $ts);
}

/**
 * Get setting value from database
 * @param string $key
 * @param string $default
 * @return string
 */
function getSetting($key, $default = '') {
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        if ($val !== false) {
            $cache[$key] = $val;
            return $val;
        }
    } catch (Exception $e) {
        // Fallback default
    }
    return $default;
}

/**
 * Update or insert setting value
 * @param string $key
 * @param string $value
 * @return bool
 */
function updateSetting($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) 
                              VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate next sequential invoice number (e.g., MT-2026-00003)
 * @return string
 */
function generateInvoiceNo() {
    $prefix = getSetting('invoice_prefix', 'MT-2026-');
    try {
        $db = getDB();
        $stmt = $db->query("SELECT MAX(sale_id) FROM sales");
        $maxId = (int)$stmt->fetchColumn();
        $next = $maxId + 1;
        return $prefix . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return $prefix . rand(10000, 99999);
    }
}

/**
 * Generate next sequential repair job code (e.g., REP-2026-00005)
 * @return string
 */
function generateRepairCode() {
    $prefix = getSetting('repair_prefix', 'REP-2026-');
    try {
        $db = getDB();
        $stmt = $db->query("SELECT MAX(repair_id) FROM repairs");
        $maxId = (int)$stmt->fetchColumn();
        $next = $maxId + 1;
        return $prefix . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return $prefix . rand(10000, 99999);
    }
}

/**
 * Record stock movement and update current stock in products table
 * @param int $productId
 * @param string $movementType (SALE, REPAIR, PURCHASE, RETURN, ADJUSTMENT)
 * @param int $qtyChange (positive to add, negative to deduct)
 * @param string $refId
 * @param string $notes
 * @return bool
 */
function recordStockMovement($productId, $movementType, $qtyChange, $refId = '', $notes = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
        $stmt->execute([$productId]);
        $curr = $stmt->fetchColumn();
        if ($curr === false) {
            return false;
        }
        $prevStock = (int)$curr;
        $newStock = max(0, $prevStock + (int)$qtyChange);

        // Update product stock
        $up = $db->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
        $up->execute([$newStock, $productId]);

        // Insert movement history
        $ins = $db->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, current_stock, reference_id, notes) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$productId, $movementType, $qtyChange, $prevStock, $newStock, $refId, $notes]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Set flash session message
 * @param string $message
 * @param string $type (success, danger, warning, info)
 */
function flashMessage($message, $type = 'success') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'message' => $message,
        'type'    => $type
    ];
}

/**
 * Render flash message HTML if present
 * @return string
 */
function renderFlash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $type = htmlspecialchars($flash['type']);
        $msg = htmlspecialchars($flash['message']);
        return "<div class='alert alert-{$type}'>
                    <div class='alert-icon'>&#9679;</div>
                    <div class='alert-content'>{$msg}</div>
                </div>";
    }
    return '';
}

/**
 * Log exception details to server log and return a generic public message.
 * @param \Throwable $e
 * @param string $publicMessage
 * @return string
 */
function logException($e, $publicMessage = 'An internal error occurred.') {
    if ($e instanceof Throwable || $e instanceof Exception) {
        error_log('[mukiltech][exception] ' . $e->getMessage() . " -- " . $e->getFile() . ':' . $e->getLine());
    } else {
        error_log('[mukiltech][exception] ' . print_r($e, true));
    }
    return $publicMessage;
}
