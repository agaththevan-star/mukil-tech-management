<?php
/**
 * MUKIL TECH - PC & Laptop Repair Invoice
 */

$pageTitle = 'Repair Service Invoice';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'technician']);

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT r.*, c.customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address 
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

// Fetch parts used
$stmt = $db->prepare("SELECT * FROM repair_parts WHERE repair_id = ?");
$stmt->execute([$id]);
$parts = $stmt->fetchAll();

$businessName = getSetting('business_name', 'MUKIL TECH');
$businessType = getSetting('business_type', 'Computer Sales • PC/Laptop Repair • Accessories');
$address      = getSetting('address', 'Vivekanandanagar, Kilinochchi, Sri Lanka');
$phone        = getSetting('phone', '077 479 5253 / 075 531 6840');
$email        = getSetting('email', 'mukiltech7@gmail.com');
$footerText   = getSetting('footer_text', 'Thank you for choosing MUKIL TECH! We appreciate your business.');
?>

<div class="actions-bar no-print" style="display:flex;justify-content:space-between;align-items:center;max-width:800px;margin:0 auto 1.5rem;">
    <a href="<?= baseUrl('repairs/view.php?id=' . $repair['repair_id']) ?>" class="btn btn-outline btn-sm">&larr; Back to Job</a>
    <div style="display:flex;gap:0.5rem;">
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <span>&#128438;</span> Print Repair Invoice
        </button>
    </div>
</div>

<div class="invoice-container">
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
            <h3>REPAIR BILL</h3>
            <p><strong>Job Ref:</strong> <span style="font-weight:800;color:var(--navy-main);"><?= e($repair['repair_code']) ?></span></p>
            <p><strong>Date:</strong> <?= formatDate($repair['completed_date'] ?: date('Y-m-d')) ?></p>
            <p><strong>Status:</strong> 
                <span class="badge <?= $repair['balance'] <= 0 ? 'badge-success' : 'badge-danger' ?>">
                    <?= $repair['balance'] <= 0 ? 'PAID & SETTLED' : 'BALANCE DUE' ?>
                </span>
            </p>
        </div>
    </div>

    <!-- Customer & Serviced Hardware Specs -->
    <div class="invoice-addresses">
        <div class="invoice-bill-to">
            <h4>Customer Details</h4>
            <div class="customer-name"><?= e($repair['customer_name']) ?></div>
            <p style="margin-top:0.25rem;">&#128222; <?= e($repair['customer_phone'] ?: '-') ?></p>
            <p>&#128205; <?= e($repair['customer_address'] ?: 'Kilinochchi, Sri Lanka') ?></p>
        </div>

        <div class="invoice-bill-to" style="border:1px dashed var(--border-medium);">
            <h4>Serviced Device Info</h4>
            <div style="font-size:1rem;font-weight:700;color:var(--text-primary);">
                <?= e($repair['brand']) ?> <?= e($repair['model']) ?> (<?= e($repair['device_type']) ?>)
            </div>
            <p style="margin-top:0.25rem;"><strong>Serial / S/N:</strong> <code><?= e($repair['serial_number'] ?: 'N/A') ?></code></p>
            <p><strong>Service Technician:</strong> <?= e($repair['technician'] ?: 'Senior Tech') ?></p>
        </div>
    </div>

    <!-- Diagnosis / Work Description Summary -->
    <div style="background:var(--bg-main);border:1px solid var(--border-light);border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.85rem;color:var(--text-primary);">
        <strong>Fault Reported:</strong> <?= e($repair['problem_description']) ?><br>
        <?php if ($repair['repair_notes']): ?>
            <strong>Resolution Completed:</strong> <?= e($repair['repair_notes']) ?>
        <?php endif; ?>
    </div>

    <!-- Parts & Labour Table -->
    <table class="table" style="border:1px solid var(--border-light);">
        <thead>
            <tr style="background:var(--navy-card);color:#ffffff;">
                <th style="background:var(--navy-card);color:#ffffff;width:50px;">#</th>
                <th style="background:var(--navy-card);color:#ffffff;">Service Item / Replacement Part</th>
                <th style="background:var(--navy-card);color:#ffffff;text-align:center;width:70px;">Qty</th>
                <th style="background:var(--navy-card);color:#ffffff;text-align:right;width:130px;">Unit Price</th>
                <th style="background:var(--navy-card);color:#ffffff;text-align:right;width:130px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($parts as $p): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <strong><?= e($p['part_name']) ?></strong>
                        <span class="badge badge-navy" style="font-size:0.65rem;margin-left:0.4rem;">Replacement Part</span>
                    </td>
                    <td style="text-align:center;"><?= $p['quantity'] ?></td>
                    <td style="text-align:right;"><?= formatCurrency($p['unit_price']) ?></td>
                    <td style="text-align:right;font-weight:700;"><?= formatCurrency($p['total']) ?></td>
                </tr>
            <?php endforeach; ?>

            <!-- Labour charge row -->
            <tr>
                <td><?= $i++ ?></td>
                <td>
                    <strong>Technical Labour & Diagnostic Servicing</strong>
                    <span class="badge badge-primary" style="font-size:0.65rem;margin-left:0.4rem;">Service Fee</span>
                </td>
                <td style="text-align:center;">1</td>
                <td style="text-align:right;"><?= formatCurrency($repair['labour_cost']) ?></td>
                <td style="text-align:right;font-weight:700;"><?= formatCurrency($repair['labour_cost']) ?></td>
            </tr>

            <?php if ($repair['other_charges'] > 0): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <strong>Additional Service / Logistics</strong>
                    </td>
                    <td style="text-align:center;">1</td>
                    <td style="text-align:right;"><?= formatCurrency($repair['other_charges']) ?></td>
                    <td style="text-align:right;font-weight:700;"><?= formatCurrency($repair['other_charges']) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="invoice-summary-box">
        <table class="invoice-totals-table">
            <tr>
                <td>Total Parts & Service:</td>
                <td style="text-align:right;"><?= formatCurrency($repair['parts_cost'] + $repair['labour_cost'] + $repair['other_charges']) ?></td>
            </tr>
            <?php if ($repair['discount'] > 0): ?>
                <tr>
                    <td>Discount Allowed:</td>
                    <td style="text-align:right;color:var(--danger);">- <?= formatCurrency($repair['discount']) ?></td>
                </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td>Final Repair Total:</td>
                <td style="text-align:right;color:var(--blue-primary);"><?= formatCurrency($repair['final_cost']) ?></td>
            </tr>
            <tr>
                <td>Advance / Payments:</td>
                <td style="text-align:right;font-weight:700;color:var(--success);"><?= formatCurrency($repair['advance_payment']) ?></td>
            </tr>
            <tr>
                <td>Balance Due:</td>
                <td style="text-align:right;font-weight:800;color:<?= $repair['balance'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                    <?= formatCurrency($repair['balance']) ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="invoice-signatures">
        <div class="signature-block">
            Customer Receipt & Handover
        </div>
        <div class="signature-block">
            Authorized Service Sign & Stamp<br>
            <span style="font-size:0.7rem;font-weight:normal;color:var(--text-muted);">MUKIL TECH Kilinochchi</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="invoice-footer">
        <p><strong>Service Warranty:</strong> 30 Days service warranty provided on technical labour. Replacement hardware parts are covered by standard brand warranty.</p>
        <p style="margin-top:4px;"><?= e($footerText) ?></p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
