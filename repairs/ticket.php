<?php
/**
 * MUKIL TECH - Repair Job Intake Ticket / Token
 */

$pageTitle = 'Repair Intake Ticket';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'technician']);

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT r.*, c.customer_name, c.phone as customer_phone, c.address as customer_address 
                      FROM repairs r 
                      LEFT JOIN customers c ON r.customer_id = c.customer_id 
                      WHERE r.repair_id = ?");
$stmt->execute([$id]);
$repair = $stmt->fetch();

if (!$repair) {
    flashMessage("Repair job not found.", 'danger');
    header("Location: " . baseUrl('repairs/index.php'));
    exit;
}

$businessName = getSetting('business_name', 'MUKIL TECH');
$businessType = getSetting('business_type', 'Computer Sales • PC/Laptop Repair • Accessories');
$address      = getSetting('address', 'Vivekanandanagar, Kilinochchi, Sri Lanka');
$phone        = getSetting('phone', '077 479 5253 / 075 531 6840');
$email        = getSetting('email', 'mukiltech7@gmail.com');
?>

<div class="actions-bar no-print" style="display:flex;justify-content:space-between;align-items:center;max-width:760px;margin:0 auto 1.5rem;">
    <a href="<?= baseUrl('repairs/view.php?id=' . $repair['repair_id']) ?>" class="btn btn-outline btn-sm">&larr; Back to Repair Center</a>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
        <span>&#128438;</span> Print Intake Ticket
    </button>
</div>

<div class="invoice-container" style="max-width:760px;">
    <div class="invoice-header">
        <div class="invoice-brand">
            <img src="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>" alt="MUKIL TECH Logo" class="invoice-logo">
            <div class="invoice-company-details">
                <h2><?= e($businessName) ?></h2>
                <div class="tagline"><?= e($businessType) ?></div>
                <p><?= e($address) ?></p>
                <p>Phone: <strong><?= e($phone) ?></strong> | Email: <?= e($email) ?></p>
            </div>
        </div>

        <div class="invoice-meta">
            <h3 style="color:var(--navy-main);">REPAIR TOKEN</h3>
            <div style="font-size:1.35rem;font-weight:900;color:var(--blue-primary);margin-top:0.25rem;">
                <?= e($repair['repair_code']) ?>
            </div>
            <p><strong>Intake Date:</strong> <?= formatDate($repair['received_date']) ?></p>
            <p><strong>Status:</strong> <span class="badge badge-status-<?= str_replace(' ', '', $repair['status']) ?>"><?= e($repair['status']) ?></span></p>
        </div>
    </div>

    <!-- Customer & Device Grid -->
    <div class="invoice-addresses">
        <div class="invoice-bill-to">
            <h4>Customer Information</h4>
            <div class="customer-name"><?= e($repair['customer_name']) ?></div>
            <p style="margin-top:0.25rem;">&#128222; <strong><?= e($repair['customer_phone']) ?></strong></p>
            <p>&#128205; <?= e($repair['customer_address'] ?: 'Kilinochchi, Sri Lanka') ?></p>
        </div>

        <div class="invoice-bill-to" style="border:1px solid var(--border-medium);">
            <h4>Device Received</h4>
            <div style="font-size:1rem;font-weight:800;color:var(--text-primary);">
                <?= e($repair['brand']) ?> <?= e($repair['model']) ?>
            </div>
            <p style="margin-top:0.25rem;"><strong>Device Type:</strong> <?= e($repair['device_type']) ?></p>
            <p><strong>Serial / Tag:</strong> <code><?= e($repair['serial_number'] ?: 'Not Specified') ?></code></p>
            <p><strong>Technician:</strong> <?= e($repair['technician'] ?: 'Senior Tech') ?></p>
        </div>
    </div>

    <!-- Problem & Diagnosis Section -->
    <div style="background:var(--bg-main);border:1px solid var(--border-light);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.25rem;color:var(--text-primary);">
        <div style="margin-bottom:0.75rem;">
            <strong style="text-transform:uppercase;font-size:0.75rem;color:var(--text-muted);display:block;margin-bottom:0.25rem;">Reported Fault / Symptoms:</strong>
            <div style="color:var(--danger);font-weight:600;font-size:0.9rem;">
                <?= nl2br(e($repair['problem_description'])) ?>
            </div>
        </div>

        <?php if ($repair['diagnosis']): ?>
            <div style="border-top:1px dashed var(--border-medium);padding-top:0.5rem;">
                <strong style="text-transform:uppercase;font-size:0.75rem;color:var(--text-muted);display:block;margin-bottom:0.25rem;">Initial Inspection Findings:</strong>
                <div style="color:var(--text-primary);font-size:0.85rem;">
                    <?= nl2br(e($repair['diagnosis'])) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Estimates & Advance Table -->
    <table class="table" style="border:1px solid var(--border-light);margin-bottom:1rem;">
        <thead>
            <tr style="background:var(--bg-main);">
                <th>Expected Ready Date</th>
                <th style="text-align:right;">Estimated Total Cost</th>
                <th style="text-align:right;">Advance Deposit Paid</th>
                <th style="text-align:right;">Estimated Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong><?= formatDate($repair['expected_date']) ?></strong></td>
                <td style="text-align:right;"><strong><?= formatCurrency($repair['estimated_cost']) ?></strong></td>
                <td style="text-align:right;color:var(--success);font-weight:700;"><?= formatCurrency($repair['advance_payment']) ?></td>
                <td style="text-align:right;font-weight:800;color:var(--text-primary);"><?= formatCurrency($repair['balance']) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Terms & Conditions -->
    <div style="background:var(--warning-bg);border:1px solid rgba(245,158,11,0.3);border-radius:var(--radius-sm);padding:0.75rem 1rem;font-size:0.75rem;color:var(--text-secondary);line-height:1.45;">
        <div style="font-weight:700;margin-bottom:0.25rem;color:var(--warning);">Terms &amp; Conditions of Service:</div>
        <ol style="margin-left:1.2rem;">
            <li>Please safeguard this ticket and present it when collecting your hardware.</li>
            <li>Cost estimates may adjust if secondary component damage is detected upon deep hardware inspection.</li>
            <li>MUKIL TECH is not liable for existing software corruption or loss of unbacked data.</li>
            <li>Hardware remaining uncollected after 60 days of completion notification will incur storage fees.</li>
        </ol>
    </div>

    <!-- Signatures -->
    <div class="invoice-signatures" style="margin-top:2.5rem;">
        <div class="signature-block">
            Customer Acceptance Signature
        </div>
        <div class="signature-block">
            Authorized Service Engineer<br>
            <span style="font-size:0.7rem;font-weight:normal;color:var(--text-muted);">MUKIL TECH Kilinochchi</span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
