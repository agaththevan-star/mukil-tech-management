<?php
/**
 * MUKIL TECH - PC & Laptop Repair Jobs Management
 */

$pageTitle = 'PC & Laptop Repair Jobs';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'technician']);

$db = getDB();

$search = trim($_GET['search'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "SELECT r.*, c.customer_name, c.phone as customer_phone 
        FROM repairs r 
        LEFT JOIN customers c ON r.customer_id = c.customer_id 
        WHERE 1=1 ";
$params = [];

if (!empty($filterStatus)) {
    $sql .= "AND r.status = ? ";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $sql .= "AND (r.repair_code LIKE ? OR c.customer_name LIKE ? OR c.phone LIKE ? OR r.brand LIKE ? OR r.model LIKE ? OR r.serial_number LIKE ?) ";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= "ORDER BY r.repair_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$repairs = $stmt->fetchAll();

// Counts per status
$statusCounts = [];
$statuses = ['Received', 'Diagnosing', 'Waiting for Parts', 'Repairing', 'Ready', 'Delivered', 'Cancelled'];
foreach ($statuses as $st) {
    $cntStmt = $db->prepare("SELECT COUNT(*) FROM repairs WHERE status = ?");
    $cntStmt->execute([$st]);
    $statusCounts[$st] = (int)$cntStmt->fetchColumn();
}
?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:1rem;flex:1;flex-wrap:wrap;">
            <div class="card-title">
                <span>&#128295;</span> PC / Laptop Repair Services (<?= count($repairs) ?>)
            </div>

            <form method="GET" action="<?= baseUrl('repairs/index.php') ?>" class="search-box">
                <span class="search-icon">&#128269;</span>
                <input type="text" name="search" class="form-control" placeholder="Search Code, Customer, Brand, S/N..." value="<?= e($search) ?>">
                <?php if (!empty($filterStatus)): ?><input type="hidden" name="status" value="<?= e($filterStatus) ?>"><?php endif; ?>
            </form>

            <?php if (!empty($search) || !empty($filterStatus)): ?>
                <a href="<?= baseUrl('repairs/index.php') ?>" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </div>

        <a href="<?= baseUrl('repairs/create.php') ?>" class="btn btn-primary">
            <span>+</span> New Repair Ticket
        </a>
    </div>

    <!-- Status Filter Bar -->
    <div style="background:#f8fafc;padding:0.75rem 1.25rem;border-bottom:1px solid var(--border-light);display:flex;gap:0.5rem;overflow-x:auto;">
        <a href="<?= baseUrl('repairs/index.php') ?>" class="cat-pill <?= empty($filterStatus) ? 'active' : '' ?>">
            All Jobs (<?= array_sum($statusCounts) ?>)
        </a>
        <?php foreach ($statuses as $st): ?>
            <a href="<?= baseUrl('repairs/index.php?status=' . urlencode($st)) ?>" class="cat-pill <?= $filterStatus === $st ? 'active' : '' ?>">
                <?= $st ?> (<?= $statusCounts[$st] ?? 0 ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Job Code</th>
                    <th>Customer</th>
                    <th>Device Details</th>
                    <th>Problem Description</th>
                    <th>Received / Due</th>
                    <th>Status</th>
                    <th>Est. / Final Cost</th>
                    <th>Balance</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($repairs)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                            No repair jobs found for this filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($repairs as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= baseUrl('repairs/view.php?id=' . $r['repair_id']) ?>" style="font-weight:700;color:var(--navy-main);">
                                    <?= e($r['repair_code']) ?>
                                </a>
                                <div style="font-size:0.7rem;color:var(--text-muted);">Tech: <?= e($r['technician'] ?: 'Senior Tech') ?></div>
                            </td>
                            <td>
                                <strong><?= e($r['customer_name'] ?? 'Customer') ?></strong>
                                <div style="font-size:0.72rem;color:var(--text-muted);">&#128222; <?= e($r['customer_phone'] ?: '-') ?></div>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--blue-primary);"><?= e($r['brand']) ?> <?= e($r['model']) ?></div>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($r['device_type']) ?> &bull; S/N: <?= e($r['serial_number'] ?: 'N/A') ?></div>
                            </td>
                            <td style="max-width:200px;font-size:0.82rem;color:var(--text-secondary);">
                                <?= e($r['problem_description']) ?>
                            </td>
                            <td>
                                <div style="font-size:0.75rem;">Rec: <?= formatDate($r['received_date']) ?></div>
                                <?php if ($r['expected_date']): ?>
                                    <div style="font-size:0.72rem;color:var(--text-muted);">Due: <?= formatDate($r['expected_date']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-status-<?= str_replace(' ', '', $r['status']) ?>">
                                    <?= e($r['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div><strong><?= formatCurrency($r['final_cost'] > 0 ? $r['final_cost'] : $r['estimated_cost']) ?></strong></div>
                                <?php if ($r['advance_payment'] > 0): ?>
                                    <div style="font-size:0.7rem;color:var(--success);">Adv: <?= formatCurrency($r['advance_payment']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['balance'] > 0): ?>
                                    <span style="color:var(--danger);font-weight:800;"><?= formatCurrency($r['balance']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--success);">&#10003; Settled</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <div class="btn-group">
                                    <a href="<?= baseUrl('repairs/view.php?id=' . $r['repair_id']) ?>" class="btn btn-primary btn-sm" title="Manage Service">
                                        Manage
                                    </a>
                                    <a href="<?= baseUrl('repairs/ticket.php?id=' . $r['repair_id']) ?>" class="btn btn-outline btn-sm" title="Print Intake Ticket">
                                        Ticket
                                    </a>
                                    <a href="<?= baseUrl('repairs/invoice.php?id=' . $r['repair_id']) ?>" class="btn btn-outline btn-sm" title="Print Repair Invoice">
                                        Bill
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
