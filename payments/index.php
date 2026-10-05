<?php
/**
 * MUKIL TECH - Payments Directory & Ledger
 */

$pageTitle = 'Payments & Transactions History';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'cashier']);

$db = getDB();

$search = trim($_GET['search'] ?? '');
$method = trim($_GET['method'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$sql = "SELECT p.*, c.customer_name, c.phone 
        FROM payments p 
        LEFT JOIN customers c ON p.customer_id = c.customer_id 
        WHERE 1=1 ";
$params = [];

if (!empty($search)) {
    $sql .= "AND (p.invoice_no LIKE ? OR c.customer_name LIKE ? OR c.phone LIKE ?) ";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($method)) {
    $sql .= "AND p.payment_method = ? ";
    $params[] = $method;
}

if (!empty($dateFrom)) {
    $sql .= "AND p.payment_date >= ? ";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $sql .= "AND p.payment_date <= ? ";
    $params[] = $dateTo;
}

$sql .= "ORDER BY p.payment_id DESC LIMIT 150";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Aggregates
$totalAmount = 0;
foreach ($payments as $p) {
    $totalAmount += $p['amount'];
}
?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:1rem;flex:1;flex-wrap:wrap;">
            <div class="card-title">
                <span>&#128179;</span> Payment Receipts & Collections (<?= count($payments) ?>)
            </div>

            <!-- Search -->
            <form method="GET" action="<?= baseUrl('payments/index.php') ?>" class="search-box">
                <span class="search-icon">&#128269;</span>
                <input type="text" name="search" class="form-control" placeholder="Search receipt / customer..." value="<?= e($search) ?>">
            </form>

            <!-- Method Filter -->
            <form method="GET" action="<?= baseUrl('payments/index.php') ?>" style="display:inline-flex;">
                <select name="method" class="form-control" onchange="this.form.submit()" style="font-size:0.82rem;padding:0.35rem 0.6rem;">
                    <option value="">All Payment Modes</option>
                    <option value="Cash" <?= $method === 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="Card" <?= $method === 'Card' ? 'selected' : '' ?>>Card</option>
                    <option value="Bank Transfer" <?= $method === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="Other" <?= $method === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </form>

            <?php if (!empty($search) || !empty($method) || !empty($dateFrom)): ?>
                <a href="<?= baseUrl('payments/index.php') ?>" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </div>

        <div style="font-weight:800;color:var(--success);font-size:1.15rem;">
            Total Collected: <?= formatCurrency($totalAmount) ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Date</th>
                    <th>Reference / Invoice</th>
                    <th>Customer</th>
                    <th>Amount Paid</th>
                    <th>Payment Mode</th>
                    <th>Received By</th>
                    <th>Notes</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                            No payment transactions recorded.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>#PAY-<?= str_pad($p['payment_id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= formatDate($p['payment_date']) ?></td>
                            <td>
                                <strong><?= e($p['invoice_no']) ?></strong>
                            </td>
                            <td>
                                <strong><?= e($p['customer_name'] ?? 'Walk-in') ?></strong>
                                <div style="font-size:0.7rem;color:var(--text-muted);"><?= e($p['phone'] ?: '-') ?></div>
                            </td>
                            <td>
                                <strong style="color:var(--success);font-size:0.95rem;"><?= formatCurrency($p['amount']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-navy"><?= e($p['payment_method']) ?></span>
                            </td>
                            <td style="font-size:0.82rem;color:var(--text-secondary);"><?= e($p['received_by'] ?: 'Staff') ?></td>
                            <td style="font-size:0.78rem;color:var(--text-muted);max-width:180px;"><?= e($p['notes'] ?: '-') ?></td>
                            <td style="text-align:right;">
                                <?php if (!empty($p['sale_id'])): ?>
                                    <a href="<?= baseUrl('sales/invoice.php?no=' . urlencode($p['invoice_no'])) ?>" class="btn btn-outline btn-sm">
                                        View Bill
                                    </a>
                                <?php elseif (!empty($p['repair_id'])): ?>
                                    <a href="<?= baseUrl('repairs/invoice.php?id=' . $p['repair_id']) ?>" class="btn btn-outline btn-sm">
                                        Repair Bill
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
