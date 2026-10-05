<?php
/**
 * MUKIL TECH - System Settings
 */

$pageTitle = 'System Settings';
require_once __DIR__ . '/../includes/header.php';

requireRole(['admin']);

$db = getDB();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        // Loop through all POST data and update settings (filtering out action/csrf)
        foreach ($_POST as $key => $value) {
            if ($key !== 'csrf_token' && $key !== 'action') {
                $stmt->execute([$value, $key]);
            }
        }
        
        $db->commit();
        flashMessage("Settings updated successfully.", 'success');
    } catch (Exception $e) {
        $db->rollBack();
        flashMessage(logException($e, 'Error updating settings: an internal error occurred.'), 'danger');
    }
    
    header("Location: " . baseUrl('admin/settings.php'));
    exit;
}

// Fetch current settings
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settingsArray = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Helper to get setting value
function getSettingVal($key, $default = '') {
    global $settingsArray;
    return $settingsArray[$key] ?? $default;
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span>&#9881;</span> System Settings
        </div>
    </div>
    
    <div class="card-body">
        <form method="POST" action="<?= baseUrl('admin/settings.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- General Info -->
                <div>
                    <h3 style="margin-top:0;margin-bottom:1rem;color:var(--navy-main);font-size:1.1rem;border-bottom:1px solid var(--border-color);padding-bottom:0.5rem;">Business Information</h3>
                    
                    <div class="form-group">
                        <label>Business Name</label>
                        <input type="text" name="business_name" class="form-control" value="<?= e(getSettingVal('business_name')) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Business Type</label>
                        <input type="text" name="business_type" class="form-control" value="<?= e(getSettingVal('business_type')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= e(getSettingVal('address')) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e(getSettingVal('email')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= e(getSettingVal('phone')) ?>">
                    </div>
                </div>

                <!-- Prefixes and Formatting -->
                <div>
                    <h3 style="margin-top:0;margin-bottom:1rem;color:var(--navy-main);font-size:1.1rem;border-bottom:1px solid var(--border-color);padding-bottom:0.5rem;">System & Formatting</h3>
                    
                    <div class="form-group">
                        <label>Invoice Prefix</label>
                        <input type="text" name="invoice_prefix" class="form-control" value="<?= e(getSettingVal('invoice_prefix')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Repair Job Prefix</label>
                        <input type="text" name="repair_prefix" class="form-control" value="<?= e(getSettingVal('repair_prefix')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Currency Symbol</label>
                        <input type="text" name="currency" class="form-control" value="<?= e(getSettingVal('currency')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Currency Code</label>
                        <input type="text" name="currency_code" class="form-control" value="<?= e(getSettingVal('currency_code')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Tax Percentage (%)</label>
                        <input type="number" step="0.01" name="tax_percentage" class="form-control" value="<?= e(getSettingVal('tax_percentage')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Receipt Printer Width</label>
                        <select name="receipt_printer_width" class="form-control">
                            <option value="80mm" <?= getSettingVal('receipt_printer_width') === '80mm' ? 'selected' : '' ?>>80mm</option>
                            <option value="58mm" <?= getSettingVal('receipt_printer_width') === '58mm' ? 'selected' : '' ?>>58mm</option>
                            <option value="A4" <?= getSettingVal('receipt_printer_width') === 'A4' ? 'selected' : '' ?>>A4 (Standard Printer)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Footer Text (Prints on Invoices)</label>
                <textarea name="footer_text" class="form-control" rows="2"><?= e(getSettingVal('footer_text')) ?></textarea>
            </div>
            
            <div style="margin-top: 1.5rem; text-align: right; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1.1rem;">
                    &#128190; Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
