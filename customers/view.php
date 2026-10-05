<?php
/**
 * MUKIL TECH - View Customer Profile, Sales & Repair History
 */

$pageTitle = 'Customer Details';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    flashMessage("Customer not found.", 'danger');
    header("Location: " . baseUrl('customers/index.php'));
    exit;
}

// 1. Fetch Sales History for this customer
$stmt = $db->prepare("SELECT s.*, u.full_name as cashier_name 
                      FROM sales s 
                      LEFT JOIN users u ON s.user_id = u.user_id 
                      WHERE s.customer_id = ? 
                      ORDER BY s.sale_id DESC");
$stmt->execute([$id]);
$salesHistory = $stmt->fetchAll();

// Calculate total spent
$totalSpent = 0;
$totalBalance = 0;
foreach ($salesHistory as $s) {
    $totalSpent += $s['grand_total'];
    $totalBalance += $s['balance'];
}

// 2. Fetch PC/Laptop Repair History for this customer
$stmt = $db->prepare("SELECT * FROM repairs WHERE customer_id = ? ORDER BY repair_id DESC");
$stmt->execute([$id]);
$repairsHistory = $stmt->fetchAll();

$totalRepairCost = 0;
foreach ($repairsHistory as $r) {
    $totalRepairCost += $r['final_cost'];
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <a href="<?= baseUrl('customers/index.php') ?>" class="btn btn-outline btn-sm">&larr; Back to Customers</a>
    </div>
    <div style="display:flex;gap:0.5rem;">
        <a href="<?= baseUrl('sales/pos.php?customer_id=' . $customer['customer_id']) ?>" class="btn btn-primary btn-sm">
            <span>&#128722;</span> New POS Sale
        </a>
        <a href="<?= baseUrl('repairs/create.php?customer_id=' . $customer['customer_id']) ?>" class="btn btn-navy btn-sm">
            <span>&#128295;</span> New Repair Job
        </a>
        <a href="<?= baseUrl('customers/edit.php?id=' . $customer['customer_id']) ?>" class="btn btn-outline btn-sm">
            <span>&#9998;</span> Edit Profile
        </a>
    </div>
</div>

<!-- Customer Bio Header Card -->
<div class="card">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.5rem;">
        <div style="display:flex;gap:1.25rem;align-items:center;">
            <div style="width:64px;height:64px;border-radius:12px;background:var(--blue-light);color:var(--blue-primary);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;">
                <?= strtoupper(substr($customer['customer_name'], 0, 1)) ?>
            </div>
            <div>
                <h2 style="font-size:1.4rem;color:var(--navy-main);font-weight:800;"><?= e($customer['customer_name']) ?></h2>
                <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:0.2rem;">
                    <span>&#128222; <strong><?= e($customer['phone']) ?></strong></span> &bull; 
                    <span>&#9993; <?= e($customer['email'] ?: 'No email') ?></span>
                </div>
                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">
                    <span>&#128205; <?= e($customer['address'] ?: 'No address provided') ?></span>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:1.5rem;background:#f8fafc;padding:0.75rem 1.25rem;border-radius:var(--radius-sm);border:1px solid var(--border-light);">
            <div>
                <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Total Purchases</div>
                <div style="font-size:1.15rem;font-weight:800;color:var(--navy-main);"><?= formatCurrency($totalSpent) ?></div>
            </div>
            <div style="border-left:1px solid var(--border-medium);padding-left:1.5rem;">
                <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Repair Services</div>
                <div style="font-size:1.15rem;font-weight:800;color:var(--blue-primary);"><?= count($repairsHistory) ?> Jobs</div>
            </div>
            <div style="border-left:1px solid var(--border-medium);padding-left:1.5rem;">
                <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Pending Balance</div>
                <div style="font-size:1.15rem;font-weight:800;color:<?= $totalBalance > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                    <?= formatCurrency($totalBalance) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 1: Sales / Billing History -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span>&#128196;</span> Sales History (<?= count($salesHistory) ?>)
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Date</th>
                    <th>Subtotal</th>
                    <th>Discount</th>
                    <th>Grand Total</th>
                    <th>Paid Amount</th>
                    <th>Balance</th>
                    <th>Payment Method</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($salesHistory)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted);">No sales records found for this customer.</td></tr>
                <?php else: ?>
                    <?php foreach ($salesHistory as $sale): ?>
                        <tr>
                            <td><strong><?= e($sale['invoice_no']) ?></strong></td>
                            <td><?= formatDate($sale['sale_date']) ?></td>
                            <td><?= formatCurrency($sale['subtotal']) ?></td>
                            <td><?= formatCurrency($sale['discount']) ?></td>
                            <td><strong><?= formatCurrency($sale['grand_total']) ?></strong></td>
                            <td><?= formatCurrency($sale['paid_amount']) ?></td>
                            <td>
                                <?php if ($sale['balance'] > 0): ?>
                                    <span style="color:var(--danger);font-weight:700;"><?= formatCurrency($sale['balance']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--success);">&#10003; Cleared</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-navy"><?= e($sale['payment_method']) ?></span></td>
                            <td>
                                <a href="<?= baseUrl('sales/invoice.php?no=' . urlencode($sale['invoice_no'])) ?>" class="btn btn-outline btn-sm">
                                    &#128438; Invoice
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Section 2: PC / Laptop Repair History -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span>&#128295;</span> PC & Laptop Repair History (<?= count($repairsHistory) ?>)
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Repair Code</th>
                    <th>Device</th>
                    <th>Serial No</th>
                    <th>Problem Description</th>
                    <th>Received Date</th>
                    <th>Status</th>
                    <th>Total Cost</th>
                    <th>Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($repairsHistory)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted);">No repair jobs logged for this customer.</td></tr>
                <?php else: ?>
                    <?php foreach ($repairsHistory as $rep): ?>
                        <tr>
                            <td><strong><?= e($rep['repair_code']) ?></strong></td>
                            <td>
                                <strong><?= e($rep['brand']) ?> <?= e($rep['model']) ?></strong>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($rep['device_type']) ?></div>
                            </td>
                            <td><code><?= e($rep['serial_number'] ?: 'N/A') ?></code></td>
                            <td style="max-width:200px;"><?= e($rep['problem_description']) ?></td>
                            <td><?= formatDate($rep['received_date']) ?></td>
                            <td>
                                <span class="badge badge-status-<?= str_replace(' ', '', $rep['status']) ?>">
                                    <?= e($rep['status']) ?>
                                </span>
                            </td>
                            <td><strong><?= formatCurrency($rep['final_cost']) ?></strong></td>
                            <td>
                                <?php if ($rep['balance'] > 0): ?>
                                    <span style="color:var(--danger);font-weight:700;"><?= formatCurrency($rep['balance']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--success);">&#10003; Paid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= baseUrl('repairs/view.php?id=' . $rep['repair_id']) ?>" class="btn btn-primary btn-sm">
                                    Manage Job
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
