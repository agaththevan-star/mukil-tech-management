<?php
/**
 * MUKIL TECH - Business & System Settings
 */

$pageTitle = 'Business Settings';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $settingsToUpdate = [
        'business_name'       => trim($_POST['business_name'] ?? 'MUKIL TECH'),
        'business_type'       => trim($_POST['business_type'] ?? 'Computer Sales • PC/Laptop Repair • Accessories'),
        'address'             => trim($_POST['address'] ?? 'Vivekanandanagar, Kilinochchi, Sri Lanka'),
        'phone'               => trim($_POST['phone'] ?? '077 479 5253 / 075 531 6840'),
        'email'               => trim($_POST['email'] ?? 'mukiltech7@gmail.com'),
        'currency'            => trim($_POST['currency'] ?? 'Rs.'),
        'tax_rate'            => trim($_POST['tax_rate'] ?? '0'),
        'invoice_footer_text' => trim($_POST['invoice_footer_text'] ?? '')
    ];

    // Handle optional logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['logo']['tmp_name'];
        $fileName = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
            $dest = __DIR__ . '/../assets/images/mukil-tech-logo.' . $ext;
            if (move_uploaded_file($fileTmp, $dest)) {
                $settingsToUpdate['logo_path'] = 'assets/images/mukil-tech-logo.' . $ext;
            }
        } else {
            $error = 'Invalid image format. Allowed formats: PNG, JPG, SVG, WEBP.';
        }
    }

    if (empty($error)) {
        try {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($settingsToUpdate as $key => $val) {
                $stmt->execute([$key, $val]);
            }
            flashMessage("System settings saved successfully.", 'success');
            header("Location: " . baseUrl('settings/index.php'));
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Read current settings
$currentSettings = [];
$rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($rows as $r) {
    $currentSettings[$r['setting_key']] = $r['setting_value'];
}
?>

<div style="max-width:800px;margin:0 auto;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>&#9881;</span> MUKIL TECH Business Profile & Invoice Configuration
            </div>
            <a href="<?= baseUrl('settings/backup.php') ?>" class="btn btn-navy btn-sm">
                <span>&#128190;</span> Database Backup & Restore &rarr;
            </a>
        </div>

        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span class="alert-icon">&#9888;</span>
                    <span class="alert-content"><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <!-- Brand & Logo Preview -->
                <div style="background:var(--bg-main);padding:1.25rem;border-radius:var(--radius-sm);border:1px solid var(--border-light);margin-bottom:1.5rem;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
                    <div style="width:90px;height:90px;background:var(--bg-card);border:1px solid var(--border-medium);border-radius:12px;padding:8px;display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);">
                        <img src="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>" alt="MUKIL TECH Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
                    </div>
                    <div style="flex:1;">
                        <label class="form-label" for="logo" style="font-weight:700;">Update Store Logo</label>
                        <input type="file" id="logo" name="logo" class="form-control" accept=".png,.jpg,.jpeg,.svg,.webp" style="padding:0.4rem;">
                        <div class="form-hint">Shown on all POS printouts, A4 customer invoices, and repair job tokens.</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="business_name">Business Name <span class="required">*</span></label>
                            <input type="text" id="business_name" name="business_name" class="form-control" required value="<?= e($currentSettings['business_name'] ?? 'MUKIL TECH') ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="business_type">Business Type / Tagline</label>
                            <input type="text" id="business_type" name="business_type" class="form-control" value="<?= e($currentSettings['business_type'] ?? 'Computer Sales • PC/Laptop Repair • Accessories') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Address / Location <span class="required">*</span></label>
                    <input type="text" id="address" name="address" class="form-control" required value="<?= e($currentSettings['address'] ?? 'Vivekanandanagar, Kilinochchi, Sri Lanka') ?>">
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="phone">Contact Phone Numbers <span class="required">*</span></label>
                            <input type="text" id="phone" name="phone" class="form-control" required value="<?= e($currentSettings['phone'] ?? '077 479 5253 / 075 531 6840') ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="email">Business Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?= e($currentSettings['email'] ?? 'mukiltech7@gmail.com') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="currency">Currency Symbol / Label</label>
                            <input type="text" id="currency" name="currency" class="form-control" value="<?= e($currentSettings['currency'] ?? 'Rs.') ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="tax_rate">Tax Rate (%)</label>
                            <input type="number" step="0.1" id="tax_rate" name="tax_rate" class="form-control" value="<?= e($currentSettings['tax_rate'] ?? '0') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="invoice_footer_text">Invoice & Receipt Footer Message</label>
                    <textarea id="invoice_footer_text" name="invoice_footer_text" class="form-control" rows="3"><?= e($currentSettings['invoice_footer_text'] ?? 'Thank you for choosing MUKIL TECH! We appreciate your business. Genuine parts & warranty guaranteed.') ?></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary" style="padding:0.75rem 1.75rem;font-size:0.95rem;">
                        <span>&#128190;</span> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
