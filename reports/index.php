<?php
/**
 * MUKIL TECH - Financial, Repair & Inventory Reports
 */

$pageTitle = 'Business Intelligence & Reports';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$db = getDB();

// Date range filters
$preset = $_GET['preset'] ?? 'month';
$today = date('Y-m-d');

if ($preset === 'today') {
    $dateFrom = $today;
    $dateTo = $today;
} elseif ($preset === 'year') {
    $dateFrom = date('Y-01-01');
    $dateTo = date('Y-12-31');
} elseif ($preset === 'custom' && !empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $dateFrom = $_GET['date_from'];
    $dateTo = $_GET['date_to'];
} else { // default 'month'
    $preset = 'month';
    $dateFrom = date('Y-m-01');
    $dateTo = date('Y-m-t');
}

// 1. Sales Data in Range
$salesStmt = $db->prepare("SELECT COUNT(*) as total_orders, 
                                  COALESCE(SUM(grand_total), 0) as total_sales,
                                  COALESCE(SUM(paid_amount), 0) as total_collected,
                                  COALESCE(SUM(balance), 0) as total_balance
                           FROM sales 
                           WHERE sale_date BETWEEN ? AND ?");
$salesStmt->execute([$dateFrom, $dateTo]);
$salesSummary = $salesStmt->fetch();

// Calculate Cost of Goods Sold (COGS) for sales in this period
$cogsStmt = $db->prepare("SELECT COALESCE(SUM(si.quantity * p.purchase_price), 0) as total_cogs
                          FROM sale_items si 
                          JOIN sales s ON si.sale_id = s.sale_id 
                          JOIN products p ON si.product_id = p.product_id 
                          WHERE s.sale_date BETWEEN ? AND ?");
$cogsStmt->execute([$dateFrom, $dateTo]);
$cogs = (float)$cogsStmt->fetchColumn();

// 2. Repair Income in Range
$repairStmt = $db->prepare("SELECT COUNT(*) as total_repairs,
                                   COALESCE(SUM(final_cost), 0) as total_billed,
                                   COALESCE(SUM(advance_payment), 0) as total_collected,
                                   COALESCE(SUM(balance), 0) as total_due
                            FROM repairs 
                            WHERE received_date BETWEEN ? AND ?");
$repairStmt->execute([$dateFrom, $dateTo]);
$repairsSummary = $repairStmt->fetch();

// 3. Operating Expenses in Range
$expStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date BETWEEN ? AND ?");
$expStmt->execute([$dateFrom, $dateTo]);
$totalExpenses = (float)$expStmt->fetchColumn();

// 4. Net Profit Calculation
// Net Profit = (Sales Collected + Repair Collected) - (COGS + Expenses)
$totalRevenue = (float)$salesSummary['total_collected'] + (float)$repairsSummary['total_collected'];
$netProfit = $totalRevenue - ($cogs + $totalExpenses);

// 5. Inventory Valuation (Snapshot)
$invStmt = $db->query("SELECT 
                        COUNT(*) as total_items,
                        COALESCE(SUM(stock_quantity * purchase_price), 0) as cost_valuation,
                        COALESCE(SUM(stock_quantity * selling_price), 0) as retail_valuation,
                        SUM(CASE WHEN stock_quantity <= minimum_stock THEN 1 ELSE 0 END) as low_stock_count
                       FROM products");
$invSummary = $invStmt->fetch();

// 6. Expense Breakdown by Category in Range
$expCatStmt = $db->prepare("SELECT category, SUM(amount) as cat_total 
                            FROM expenses 
                            WHERE expense_date BETWEEN ? AND ? 
                            GROUP BY category ORDER BY cat_total DESC");
$expCatStmt->execute([$dateFrom, $dateTo]);
$expensesByCategory = $expCatStmt->fetchAll();

// 7. Repair Status Breakdown in Range
$repStatusStmt = $db->prepare("SELECT status, COUNT(*) as count, SUM(final_cost) as total_cost 
                               FROM repairs 
                               WHERE received_date BETWEEN ? AND ? 
                               GROUP BY status");
$repStatusStmt->execute([$dateFrom, $dateTo]);
$repairStatusBreakdown = $repStatusStmt->fetchAll();
?>

<!-- Filter and Action Bar -->
<div class="actions-bar no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;background:var(--bg-card);padding:1rem 1.25rem;border-radius:var(--radius-md);border:1px solid var(--border-light);box-shadow:var(--shadow-sm);flex-wrap:wrap;gap:1rem;">
    <!-- Presets -->
    <div class="btn-group">
        <a href="<?= baseUrl('reports/index.php?preset=today') ?>" class="btn <?= $preset === 'today' ? 'btn-navy' : 'btn-outline' ?> btn-sm">Today</a>
        <a href="<?= baseUrl('reports/index.php?preset=month') ?>" class="btn <?= $preset === 'month' ? 'btn-navy' : 'btn-outline' ?> btn-sm">This Month</a>
        <a href="<?= baseUrl('reports/index.php?preset=year') ?>" class="btn <?= $preset === 'year' ? 'btn-navy' : 'btn-outline' ?> btn-sm">This Year</a>
    </div>

    <!-- Custom Date Range Form -->
    <form method="GET" action="<?= baseUrl('reports/index.php') ?>" style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
        <input type="hidden" name="preset" value="custom">
        <span style="font-size:0.85rem;color:var(--text-muted);">From:</span>
        <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>" style="padding:0.35rem 0.6rem;font-size:0.82rem;">
        <span style="font-size:0.85rem;color:var(--text-muted);">To:</span>
        <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>" style="padding:0.35rem 0.6rem;font-size:0.82rem;">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>

    <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
        <span>&#128438;</span> Print Statement
    </button>
</div>

<!-- Report Document Container for Screen & Print -->
<div class="report-document" style="background:var(--bg-card);padding:1.5rem;border-radius:var(--radius-md);border:1px solid var(--border-light);">
    
    <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid var(--border-light);padding-bottom:1rem;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.4rem;color:var(--text-primary);font-weight:800;">MUKIL TECH - Financial &amp; Performance Report</h2>
            <div style="font-size:0.85rem;color:var(--text-muted);margin-top:0.25rem;">
                Reporting Period: <strong style="color:var(--text-primary);"><?= formatDate($dateFrom) ?></strong> to <strong style="color:var(--text-primary);"><?= formatDate($dateTo) ?></strong>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:0.75rem;color:var(--text-muted);">Generated On: <?= formatDate(date('Y-m-d H:i:s'), true) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);">Kilinochchi Branch</div>
        </div>
    </div>

    <!-- Profit & Loss Core KPI Cards -->
    <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title" style="color:var(--text-secondary);font-weight:600;font-size:0.85rem;">POS Retail Sales</span>
                <span class="stat-icon">&#128722;</span>
            </div>
            <div class="stat-value" style="color:var(--text-primary);font-weight:800;"><?= formatCurrency($salesSummary['total_collected']) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.3rem;">
                Total Billed: <?= formatCurrency($salesSummary['total_sales']) ?> (<?= $salesSummary['total_orders'] ?> Invoices)
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title" style="color:var(--text-secondary);font-weight:600;font-size:0.85rem;">PC Repair Revenue</span>
                <span class="stat-icon">&#128295;</span>
            </div>
            <div class="stat-value" style="color:var(--cyan-bright);font-weight:800;"><?= formatCurrency($repairsSummary['total_collected']) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.3rem;">
                Jobs Processed: <?= $repairsSummary['total_repairs'] ?> jobs
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title" style="color:var(--text-secondary);font-weight:600;font-size:0.85rem;">Operating Expenses</span>
                <span class="stat-icon">&#128184;</span>
            </div>
            <div class="stat-value" style="color:var(--danger);font-weight:800;"><?= formatCurrency($totalExpenses) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.3rem;">
                Rent, power, internet & logistics
            </div>
        </div>

        <div class="stat-card" style="border:2px solid <?= $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' ?>;background:<?= $netProfit >= 0 ? 'var(--success-bg)' : 'var(--danger-bg)' ?>;">
            <div class="stat-header">
                <span class="stat-title" style="color:var(--text-primary);font-weight:800;font-size:0.85rem;">Estimated Net Profit</span>
                <span class="stat-icon"><?= $netProfit >= 0 ? '&#128200;' : '&#128201;' ?></span>
            </div>
            <div class="stat-value" style="color:<?= $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:800;">
                <?= formatCurrency($netProfit) ?>
            </div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.3rem;">
                Formula: Revenue - (COGS: <?= formatCurrency($cogs) ?> + Exp)
            </div>
        </div>
    </div>

    <!-- P&L Table Breakdown -->
    <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:1.5rem;align-items:start;margin-bottom:2rem;">
        <div>
            <h3 style="font-size:1.1rem;color:var(--text-primary);margin-bottom:0.75rem;">Income & Cost Statement</h3>
            <table class="table" style="border:1px solid var(--border-light);">
                <tbody>
                    <tr style="background:var(--bg-main);font-weight:700;">
                        <td style="color:var(--text-secondary);font-weight:700;">1. Gross Revenues</td>
                        <td style="text-align:right;color:var(--text-secondary);font-weight:700;">Amount (Rs.)</td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;&bull; Product / Accessory Counter Sales</td>
                        <td style="text-align:right;"><?= formatCurrency($salesSummary['total_collected']) ?></td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;&bull; PC & Laptop Repair Service Fees</td>
                        <td style="text-align:right;"><?= formatCurrency($repairsSummary['total_collected']) ?></td>
                    </tr>
                    <tr style="font-weight:700;">
                        <td style="color:var(--text-primary);">Total Revenue Realized</td>
                        <td style="text-align:right;color:var(--cyan-bright);font-weight:800;"><?= formatCurrency($totalRevenue) ?></td>
                    </tr>

                    <tr style="background:var(--bg-main);font-weight:700;">
                        <td style="color:var(--text-secondary);font-weight:700;">2. Cost of Sales & Expenses</td>
                        <td style="text-align:right;color:var(--text-secondary);font-weight:700;">Amount (Rs.)</td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;&bull; Cost of Goods Sold (Purchase Cost of Inventory Sold)</td>
                        <td style="text-align:right;color:var(--danger);"><?= formatCurrency($cogs) ?></td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;&bull; Operating Expenses (Shop overheads)</td>
                        <td style="text-align:right;color:var(--danger);"><?= formatCurrency($totalExpenses) ?></td>
                    </tr>
                    <tr style="font-weight:700;">
                        <td style="color:var(--text-primary);">Total Outflow</td>
                        <td style="text-align:right;color:var(--danger);font-weight:800;"><?= formatCurrency($cogs + $totalExpenses) ?></td>
                    </tr>

                    <tr style="background:var(--blue-primary);color:#ffffff;font-size:1.05rem;font-weight:800;">
                        <td style="color:#ffffff !important;font-weight:800;">NET PROFIT / (LOSS)</td>
                        <td style="text-align:right;color:#ffffff !important;font-weight:800;"><?= formatCurrency($netProfit) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <h3 style="font-size:1.1rem;color:var(--text-primary);margin-bottom:0.75rem;">Expenses by Category</h3>
            <table class="table" style="border:1px solid var(--border-light);">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th style="text-align:right;">Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expensesByCategory)): ?>
                        <tr><td colspan="2" style="text-align:center;padding:1rem;color:var(--text-muted);">No expenses recorded for this interval.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expensesByCategory as $ec): ?>
                            <tr>
                                <td><strong><?= e($ec['category']) ?></strong></td>
                                <td style="text-align:right;font-weight:600;color:var(--danger);"><?= formatCurrency($ec['cat_total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Inventory Health & Repair Distribution -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
        <!-- Inventory Valuation -->
        <div class="card" style="margin-bottom:0;">
            <div class="card-header">
                <div class="card-title">
                    <span>&#128230;</span> Current Inventory Health & Valuation
                </div>
            </div>
            <div class="card-body">
                <div class="summary-line">
                    <span>Catalog Product Lines:</span>
                    <strong><?= $invSummary['total_items'] ?> Products</strong>
                </div>
                <div class="summary-line" style="margin-top:0.4rem;">
                    <span>Total Valuation at Cost Price:</span>
                    <strong style="color:var(--text-primary);"><?= formatCurrency($invSummary['cost_valuation']) ?></strong>
                </div>
                <div class="summary-line" style="margin-top:0.4rem;">
                    <span>Total Valuation at Retail Selling Price:</span>
                    <strong style="color:var(--cyan-bright);"><?= formatCurrency($invSummary['retail_valuation']) ?></strong>
                </div>
                <div class="summary-line" style="margin-top:0.4rem;">
                    <span>Expected Inventory Margin:</span>
                    <strong style="color:var(--success);"><?= formatCurrency($invSummary['retail_valuation'] - $invSummary['cost_valuation']) ?></strong>
                </div>
                <div class="summary-line" style="margin-top:0.4rem;">
                    <span>Items Requiring Restock:</span>
                    <strong style="color:<?= $invSummary['low_stock_count'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                        <?= $invSummary['low_stock_count'] ?> Products
                    </strong>
                </div>
            </div>
        </div>

        <!-- Repair Job Status Counts -->
        <div class="card" style="margin-bottom:0;">
            <div class="card-header">
                <div class="card-title">
                    <span>&#128295;</span> Repair Service Jobs in Period
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th style="text-align:center;">Jobs</th>
                            <th style="text-align:right;">Billed Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($repairStatusBreakdown)): ?>
                            <tr><td colspan="3" style="text-align:center;padding:1rem;color:var(--text-muted);">No repair jobs logged in this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($repairStatusBreakdown as $rs): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-status-<?= str_replace(' ', '', $rs['status']) ?>"><?= e($rs['status']) ?></span>
                                    </td>
                                    <td style="text-align:center;"><strong><?= $rs['count'] ?></strong></td>
                                    <td style="text-align:right;font-weight:700;"><?= formatCurrency($rs['total_cost']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

