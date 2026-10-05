<?php
/**
 * MUKIL TECH - Invoices & Sales Management
 */

$pageTitle = 'Sales & Invoices Directory';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'cashier']);

$db = getDB();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$sql = "SELECT s.*, c.customer_name, c.phone, u.full_name as cashier_name 
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.customer_id 
        LEFT JOIN users u ON s.user_id = u.user_id 
        WHERE 1=1 ";
$params = [];

if (!empty($search)) {
    $sql .= "AND (s.invoice_no LIKE ? OR c.customer_name LIKE ? OR c.phone LIKE ?) ";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($status)) {
    if ($status === 'unpaid') {
        $sql .= "AND s.balance > 0 ";
    } elseif ($status === 'paid') {
        $sql .= "AND s.balance <= 0 ";
    }
}

if (!empty($dateFrom)) {
    $sql .= "AND s.sale_date >= ? ";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $sql .= "AND s.sale_date <= ? ";
    $params[] = $dateTo;
}

$sql .= "ORDER BY s.sale_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// Totals
$totalRevenue = 0;
$totalBalanceDue = 0;
foreach ($sales as $s) {
    $totalRevenue += $s['paid_amount'];
    $totalBalanceDue += $s['balance'];
}
?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:1rem;flex:1;flex-wrap:wrap;">
            <div class="card-title">
                <span>&#128196;</span> Sales & Invoices (<?= count($sales) ?>)
            </div>

            <!-- Search Form -->
            <form method="GET" action="<?= baseUrl('sales/index.php') ?>" class="search-box">
                <span class="search-icon">&#128269;</span>
                <input type="text" name="search" class="form-control" placeholder="Search Invoice No, customer..." value="<?= e($search) ?>">
                <?php if (!empty($status)): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            </form>

            <!-- Quick Status Filter -->
            <div class="btn-group">
                <a href="<?= baseUrl('sales/index.php') ?>" class="btn <?= empty($status) ? 'btn-navy' : 'btn-outline' ?> btn-sm">All</a>
                <a href="<?= baseUrl('sales/index.php?status=paid') ?>" class="btn <?= $status === 'paid' ? 'btn-success' : 'btn-outline' ?> btn-sm">Paid</a>
                <a href="<?= baseUrl('sales/index.php?status=unpaid') ?>" class="btn <?= $status === 'unpaid' ? 'btn-danger' : 'btn-outline' ?> btn-sm">Unpaid / Due</a>
            </div>

            <?php if (!empty($search) || !empty($status) || !empty($dateFrom)): ?>
                <a href="<?= baseUrl('sales/index.php') ?>" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </div>

        <a href="<?= baseUrl('sales/pos.php') ?>" class="btn btn-primary">
            <span>&#128722;</span> New POS Bill
        </a>
    </div>

    <!-- Summary Bar -->
    <div style="background:#f8fafc;padding:0.75rem 1.25rem;border-bottom:1px solid var(--border-light);display:flex;gap:2rem;font-size:0.85rem;">
        <div>Collected Revenue: <strong style="color:var(--success);"><?= formatCurrency($totalRevenue) ?></strong></div>
        <div>Outstanding Receivables: <strong style="color:var(--danger);"><?= formatCurrency($totalBalanceDue) ?></strong></div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Subtotal</th>
                    <th>Discount</th>
                    <th>Grand Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="11" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                            No sales records found matching the criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td>
                                <a href="<?= baseUrl('sales/invoice.php?no=' . urlencode($s['invoice_no'])) ?>" style="font-weight:700;color:var(--blue-primary);">
                                    <?= e($s['invoice_no']) ?>
                                </a>
                            </td>
                            <td><?= formatDate($s['sale_date']) ?></td>
                            <td>
                                <strong><?= e($s['customer_name'] ?? 'Walk-in') ?></strong>
                                <div style="font-size:0.7rem;color:var(--text-muted);"><?= e($s['phone'] ?: '-') ?></div>
                            </td>
                            <td><?= formatCurrency($s['subtotal']) ?></td>
                            <td><?= $s['discount'] > 0 ? formatCurrency($s['discount']) : '-' ?></td>
                            <td><strong><?= formatCurrency($s['grand_total']) ?></strong></td>
                            <td style="color:var(--success);font-weight:600;"><?= formatCurrency($s['paid_amount']) ?></td>
                            <td>
                                <?php if ($s['balance'] > 0): ?>
                                    <span style="color:var(--danger);font-weight:800;"><?= formatCurrency($s['balance']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--success);">&#10003; Paid</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-navy"><?= e($s['payment_method']) ?></span></td>
                            <td>
                                <span class="badge <?= $s['balance'] <= 0 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $s['balance'] <= 0 ? 'Completed' : 'Pending Due' ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div class="btn-group">
                                    <a href="<?= baseUrl('sales/invoice.php?no=' . urlencode($s['invoice_no'])) ?>" class="btn btn-outline btn-sm" title="Print Standard A4 Invoice">
                                        &#128438; Invoice
                                    </a>
                                    <a href="<?= baseUrl('sales/invoice.php?no=' . urlencode($s['invoice_no']) . '&format=thermal') ?>" class="btn btn-outline btn-sm" title="Print 80mm Receipt">
                                        &#129534; Receipt
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
