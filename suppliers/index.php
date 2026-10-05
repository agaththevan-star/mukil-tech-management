<?php
/**
 * MUKIL TECH - Supplier Management
 */

$pageTitle = 'Suppliers Directory';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$db = getDB();
$search = trim($_GET['search'] ?? '');

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verifyCsrfToken();
    $suppId = (int)$_POST['supplier_id'];
    try {
        $stmt = $db->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
        $stmt->execute([$suppId]);
        flashMessage("Supplier #$suppId removed successfully.", 'success');
    } catch (Exception $e) {
        flashMessage("Cannot delete supplier with linked products or purchases.", 'danger');
    }
    header("Location: " . baseUrl('suppliers/index.php'));
    exit;
}

// Fetch suppliers
$sql = "SELECT s.*, 
        (SELECT COUNT(*) FROM products p WHERE p.supplier_id = s.supplier_id) as total_products,
        (SELECT COUNT(*) FROM purchases pu WHERE pu.supplier_id = s.supplier_id) as total_purchases
        FROM suppliers s ";
$params = [];

if (!empty($search)) {
    $sql .= "WHERE s.supplier_name LIKE ? OR s.company_name LIKE ? OR s.phone LIKE ? OR s.email LIKE ? ";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$sql .= "ORDER BY s.supplier_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$suppliers = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:1rem;flex:1;flex-wrap:wrap;">
            <div class="card-title">
                <span>&#128179;</span> Hardware & Spares Suppliers (<?= count($suppliers) ?>)
            </div>

            <form method="GET" action="<?= baseUrl('suppliers/index.php') ?>" class="search-box">
                <span class="search-icon">&#128269;</span>
                <input type="text" name="search" class="form-control" placeholder="Search supplier or company..." value="<?= e($search) ?>">
            </form>
            <?php if (!empty($search)): ?>
                <a href="<?= baseUrl('suppliers/index.php') ?>" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </div>

        <a href="<?= baseUrl('suppliers/add.php') ?>" class="btn btn-primary">
            <span>+</span> Add New Supplier
        </a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Supplier / Contact</th>
                    <th>Company Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Products</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                            No suppliers found matching your query.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td>#<?= $s['supplier_id'] ?></td>
                            <td>
                                <a href="<?= baseUrl('suppliers/view.php?id=' . $s['supplier_id']) ?>" style="font-weight:700;color:var(--navy-main);">
                                    <?= e($s['supplier_name']) ?>
                                </a>
                            </td>
                            <td>
                                <strong><?= e($s['company_name']) ?></strong>
                            </td>
                            <td><?= e($s['phone'] ?: '-') ?></td>
                            <td><?= e($s['email'] ?: '-') ?></td>
                            <td style="max-width:200px;"><?= e($s['address'] ?: '-') ?></td>
                            <td>
                                <span class="badge badge-primary"><?= $s['total_products'] ?> Products</span>
                            </td>
                            <td style="text-align:right;">
                                <div class="btn-group">
                                    <a href="<?= baseUrl('suppliers/view.php?id=' . $s['supplier_id']) ?>" class="btn btn-outline btn-sm">
                                        View
                                    </a>
                                    <a href="<?= baseUrl('suppliers/edit.php?id=' . $s['supplier_id']) ?>" class="btn btn-outline btn-sm">
                                        Edit
                                    </a>
                                    <form method="POST" action="<?= baseUrl('suppliers/index.php') ?>" style="display:inline;" onsubmit="return confirm('Delete supplier <?= e($s['company_name']) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="supplier_id" value="<?= $s['supplier_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" <?= $s['total_products'] > 0 ? 'disabled title="Linked to existing products"' : '' ?>>
                                            &#128465;
                                        </button>
                                    </form>
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
