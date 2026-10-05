<?php
/**
 * MUKIL TECH - Main Administration & Operations Dashboard
 */

$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// 1. Today's Financial Metrics
$todaySales = 0;
$todayRepairIncome = 0;
$todayExpenses = 0;

try {
    $stmt = $db->query("SELECT COALESCE(SUM(paid_amount), 0) FROM sales WHERE sale_date = CURDATE()");
    $todaySales = (float)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE repair_id IS NOT NULL AND payment_date = CURDATE()");
    $todayRepairIncome = (float)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date = CURDATE()");
    $todayExpenses = (float)$stmt->fetchColumn();
} catch (Exception $e) {}

$todayProfit = ($todaySales + $todayRepairIncome) - $todayExpenses;

// 2. Overall Business Statistics
$totalProducts = 0;
$lowStockCount = 0;
$pendingRepairs = 0;
$completedRepairs = 0;
$totalCustomers = 0;
$unpaidInvoicesCount = 0;
$outstandingBalance = 0;

try {
    $totalProducts = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $lowStockCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= minimum_stock")->fetchColumn();
    $pendingRepairs = (int)$db->query("SELECT COUNT(*) FROM repairs WHERE status NOT IN ('Delivered', 'Cancelled')")->fetchColumn();
    $completedRepairs = (int)$db->query("SELECT COUNT(*) FROM repairs WHERE status IN ('Ready', 'Delivered')")->fetchColumn();
    $totalCustomers = (int)$db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $unpaidInvoicesCount = (int)$db->query("SELECT COUNT(*) FROM invoices WHERE balance > 0")->fetchColumn();
    $outstandingBalance = (float)$db->query("SELECT COALESCE(SUM(balance), 0) FROM invoices")->fetchColumn();
} catch (Exception $e) {}

// 3. Recent Sales (Latest 5)
$recentSales = [];
try {
    $stmt = $db->query("SELECT s.*, c.customer_name, c.phone 
                        FROM sales s 
                        LEFT JOIN customers c ON s.customer_id = c.customer_id 
                        ORDER BY s.sale_id DESC LIMIT 5");
    $recentSales = $stmt->fetchAll();
} catch (Exception $e) {}

// 4. Recent Repairs (Latest 5)
$recentRepairs = [];
try {
    $stmt = $db->query("SELECT r.*, c.customer_name, c.phone 
                        FROM repairs r 
                        LEFT JOIN customers c ON r.customer_id = c.customer_id 
                        ORDER BY r.repair_id DESC LIMIT 5");
    $recentRepairs = $stmt->fetchAll();
} catch (Exception $e) {}

// 5. Recent Payments (Latest 5)
$recentPayments = [];
try {
    $stmt = $db->query("SELECT p.*, c.customer_name 
                        FROM payments p 
                        LEFT JOIN customers c ON p.customer_id = c.customer_id 
                        ORDER BY p.payment_id DESC LIMIT 5");
    $recentPayments = $stmt->fetchAll();
} catch (Exception $e) {}

// 6. Low stock products list
$lowStockItems = [];
if ($lowStockCount > 0) {
    try {
        $stmt = $db->query("SELECT product_id, product_name, brand, sku, stock_quantity, minimum_stock, selling_price 
                            FROM products 
                            WHERE stock_quantity <= minimum_stock 
                            ORDER BY stock_quantity ASC LIMIT 4");
        $lowStockItems = $stmt->fetchAll();
    } catch (Exception $e) {}
}
?>

<!-- Low Stock Warning Banner -->
<?php if (!empty($lowStockItems)): ?>
    <div class="alert alert-warning" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <span style="font-size:1.4rem;">&#9888;</span>
            <div>
                <strong>Low Stock Alert:</strong> <?= $lowStockCount ?> item(s) are at or below minimum threshold.
                <span style="color:inherit;opacity:0.9;margin-left:0.5rem;font-size:0.8rem;">
                    (e.g., <?= e($lowStockItems[0]['product_name']) ?>: <?= $lowStockItems[0]['stock_quantity'] ?> left)
                </span>
            </div>
        </div>
        <a href="<?= baseUrl('products/index.php?filter=low_stock') ?>" class="btn btn-warning btn-sm" style="background:#d97706;border:none;">
            View Low Stock Items &rarr;
        </a>
    </div>
<?php endif; ?>

<!-- Today's Financial Summary Cards -->
<div style="margin-bottom:0.75rem;">
    <h3 style="font-size:0.9rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-secondary);font-weight:700;">
        Today's Performance (<?= date('d M Y') ?>)
    </h3>
</div>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon primary">&#128722;</div>
        <div class="stat-details">
            <div class="stat-label">Today's Sales</div>
            <div class="stat-value"><?= formatCurrency($todaySales) ?></div>
            <div class="stat-sub">POS counter billing</div>
        </div>
    </div>

    <div class="stat-card success">
        <div class="stat-icon success">&#128295;</div>
        <div class="stat-details">
            <div class="stat-label">Repair Income</div>
            <div class="stat-value"><?= formatCurrency($todayRepairIncome) ?></div>
            <div class="stat-sub">Parts + service labour</div>
        </div>
    </div>

    <div class="stat-card warning">
        <div class="stat-icon warning">&#128184;</div>
        <div class="stat-details">
            <div class="stat-label">Today's Expenses</div>
            <div class="stat-value"><?= formatCurrency($todayExpenses) ?></div>
            <div class="stat-sub">Operating & utilities</div>
        </div>
    </div>

    <div class="stat-card <?= $todayProfit >= 0 ? 'success' : 'danger' ?>">
        <div class="stat-icon <?= $todayProfit >= 0 ? 'success' : 'danger' ?>">&#128176;</div>
        <div class="stat-details">
            <div class="stat-label">Today's Net Profit</div>
            <div class="stat-value"><?= formatCurrency($todayProfit) ?></div>
            <div class="stat-sub">Sales + Repairs - Expenses</div>
        </div>
    </div>
</div>

<!-- Overall System Overview Cards -->
<div style="margin-bottom:0.75rem;margin-top:1rem;">
    <h3 style="font-size:0.9rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-secondary);font-weight:700;">
        Overall Operations
    </h3>
</div>

<div class="stats-grid">
    <div class="stat-card navy">
        <div class="stat-icon navy">&#128230;</div>
        <div class="stat-details">
            <div class="stat-label">Total Products</div>
            <div class="stat-value"><?= $totalProducts ?></div>
            <div class="stat-sub"><?= $lowStockCount ?> low stock items</div>
        </div>
    </div>

    <div class="stat-card primary">
        <div class="stat-icon primary">&#128337;</div>
        <div class="stat-details">
            <div class="stat-label">Active Repairs</div>
            <div class="stat-value"><?= $pendingRepairs ?></div>
            <div class="stat-sub">Jobs in service queue</div>
        </div>
    </div>

    <div class="stat-card success">
        <div class="stat-icon success">&#9989;</div>
        <div class="stat-details">
            <div class="stat-label">Completed Repairs</div>
            <div class="stat-value"><?= $completedRepairs ?></div>
            <div class="stat-sub">Ready or delivered</div>
        </div>
    </div>

    <div class="stat-card navy">
        <div class="stat-icon navy">&#128101;</div>
        <div class="stat-details">
            <div class="stat-label">Registered Customers</div>
            <div class="stat-value"><?= $totalCustomers ?></div>
            <div class="stat-sub">Active client accounts</div>
        </div>
    </div>

    <div class="stat-card <?= $outstandingBalance > 0 ? 'danger' : 'success' ?>">
        <div class="stat-icon <?= $outstandingBalance > 0 ? 'danger' : 'success' ?>">&#128179;</div>
        <div class="stat-details">
            <div class="stat-label">Outstanding Balance</div>
            <div class="stat-value"><?= formatCurrency($outstandingBalance) ?></div>
            <div class="stat-sub"><?= $unpaidInvoicesCount ?> unpaid / partial invoices</div>
        </div>
    </div>
</div>

<!-- Grid for Recent Activity Tables -->
<div style="display:grid;grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));gap:1.5rem;margin-top:1.5rem;">
    
    <!-- Recent Sales Table -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <div class="card-title">
                <span>&#128722;</span> Recent Sales
            </div>
            <a href="<?= baseUrl('sales/index.php') ?>" class="btn btn-outline btn-sm">View All Sales &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentSales)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">No sales recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--blue-primary);"><?= e($sale['invoice_no']) ?></strong>
                                    <div style="font-size:0.7rem;color:var(--text-muted);"><?= formatDate($sale['sale_date']) ?></div>
                                </td>
                                <td>
                                    <?= e($sale['customer_name'] ?? 'Walk-in Customer') ?>
                                    <div style="font-size:0.7rem;color:var(--text-muted);"><?= e($sale['phone'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <strong><?= formatCurrency($sale['grand_total']) ?></strong>
                                    <?php if ($sale['balance'] > 0): ?>
                                        <div style="font-size:0.7rem;color:var(--danger);">Bal: <?= formatCurrency($sale['balance']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-navy"><?= e($sale['payment_method']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= baseUrl('sales/invoice.php?no=' . urlencode($sale['invoice_no'])) ?>" class="btn btn-outline btn-sm" title="View & Print Invoice">
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

    <!-- Recent PC / Laptop Repairs Table -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <div class="card-title">
                <span>&#128295;</span> Recent PC & Laptop Repairs
            </div>
            <a href="<?= baseUrl('repairs/index.php') ?>" class="btn btn-outline btn-sm">All Repairs &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Job Code</th>
                        <th>Customer / Device</th>
                        <th>Problem</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentRepairs)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">No repair jobs recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentRepairs as $rep): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--navy-main);"><?= e($rep['repair_code']) ?></strong>
                                    <div style="font-size:0.7rem;color:var(--text-muted);"><?= formatDate($rep['received_date']) ?></div>
                                </td>
                                <td>
                                    <strong><?= e($rep['customer_name'] ?? 'Customer') ?></strong>
                                    <div style="font-size:0.72rem;color:var(--blue-primary);font-weight:600;">
                                        <?= e($rep['brand']) ?> <?= e($rep['model']) ?> (<?= e($rep['device_type']) ?>)
                                    </div>
                                </td>
                                <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= e($rep['problem_description']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-status-<?= str_replace(' ', '', $rep['status']) ?>">
                                        <?= e($rep['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= baseUrl('repairs/view.php?id=' . $rep['repair_id']) ?>" class="btn btn-primary btn-sm">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Recent Payments Table -->
<div class="card" style="margin-top:1.5rem;">
    <div class="card-header">
        <div class="card-title">
            <span>&#128179;</span> Recent Payments Received
        </div>
        <div style="font-size:0.8rem;color:var(--text-muted);">
            Audited cash & online transactions
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Reference / Invoice</th>
                    <th>Customer</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th>Received By</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentPayments)): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">No payments logged yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentPayments as $pay): ?>
                        <tr>
                            <td>#PAY-<?= str_pad((string)$pay['payment_id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <strong><?= e($pay['invoice_no'] ?? '-') ?></strong>
                                <?php if ($pay['repair_id']): ?>
                                    <span class="badge badge-primary">Repair</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($pay['customer_name'] ?? '-') ?></td>
                            <td><?= formatDate($pay['payment_date']) ?></td>
                            <td><span class="badge badge-navy"><?= e($pay['payment_method']) ?></span></td>
                            <td><?= e($pay['received_by'] ?? 'Counter') ?></td>
                            <td><strong style="color:var(--success);"><?= formatCurrency($pay['amount']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
