<?php
/**
 * MUKIL TECH - Products & Inventory Management
 */

$pageTitle = 'Products & Accessories Inventory';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

$search = trim($_GET['search'] ?? '');
$filterCat = (int)($_GET['category_id'] ?? 0);
$filterLow = isset($_GET['filter']) && $_GET['filter'] === 'low_stock';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && hasRole(['admin'])) {
    verifyCsrfToken();
    $prodId = (int)$_POST['product_id'];
    try {
        $stmt = $db->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$prodId]);
        flashMessage("Product #$prodId deleted successfully.", 'success');
    } catch (Exception $e) {
        flashMessage("Cannot delete product linked to existing sales, repairs or inventory history.", 'danger');
    }
    header("Location: " . baseUrl('products/index.php'));
    exit;
}

// Build query
$sql = "SELECT p.*, c.category_name, s.company_name as supplier_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
        WHERE 1=1 ";
$params = [];

if ($filterLow) {
    $sql .= "AND p.stock_quantity <= p.minimum_stock ";
}

if ($filterCat > 0) {
    $sql .= "AND p.category_id = ? ";
    $params[] = $filterCat;
}

if (!empty($search)) {
    $sql .= "AND (p.product_name LIKE ? OR p.brand LIKE ? OR p.model LIKE ? OR p.sku LIKE ?) ";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= "ORDER BY p.product_name ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories for filter dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:1rem;flex:1;flex-wrap:wrap;">
            <div class="card-title">
                <span>&#128230;</span> Product Inventory (<?= count($products) ?>)
            </div>

            <!-- Search -->
            <form method="GET" action="<?= baseUrl('products/index.php') ?>" class="search-box">
                <span class="search-icon">&#128269;</span>
                <input type="text" name="search" class="form-control" placeholder="Search SKU, name, brand..." value="<?= e($search) ?>">
                <?php if ($filterCat): ?><input type="hidden" name="category_id" value="<?= $filterCat ?>"><?php endif; ?>
                <?php if ($filterLow): ?><input type="hidden" name="filter" value="low_stock"><?php endif; ?>
            </form>

            <!-- Category Filter -->
            <form method="GET" action="<?= baseUrl('products/index.php') ?>" style="display:inline-flex;">
                <select name="category_id" class="form-control" style="font-size:0.82rem;padding:0.45rem 0.75rem;" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $filterCat == $cat['category_id'] ? 'selected' : '' ?>>
                            <?= e($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <!-- Low Stock Filter Toggle -->
            <a href="<?= baseUrl('products/index.php' . ($filterLow ? '' : '?filter=low_stock')) ?>" class="btn <?= $filterLow ? 'btn-danger' : 'btn-outline' ?> btn-sm">
                &#9888; Low Stock Only
            </a>

            <?php if (!empty($search) || $filterCat || $filterLow): ?>
                <a href="<?= baseUrl('products/index.php') ?>" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </div>

        <div style="display:flex;gap:0.5rem;">
            <?php if (hasRole(['admin'])): ?>
                <a href="<?= baseUrl('products/stock.php') ?>" class="btn btn-outline btn-sm">
                    <span>&#128259;</span> Stock Adjustments
                </a>
                <a href="<?= baseUrl('products/add.php') ?>" class="btn btn-primary">
                    <span>+</span> Add Product
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>SKU / Code</th>
                    <th>Product Description</th>
                    <th>Category</th>
                    <th>Purchase</th>
                    <th>Selling Price</th>
                    <th>Profit Margin</th>
                    <th>Current Stock</th>
                    <th>Min Level</th>
                    <?php if (hasRole(['admin'])): ?>
                        <th style="text-align:right;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                            No products match the selected criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php 
                            $isLow = $p['stock_quantity'] <= $p['minimum_stock'];
                            $margin = $p['selling_price'] - $p['purchase_price'];
                            $marginPercent = $p['purchase_price'] > 0 ? round(($margin / $p['purchase_price']) * 100) : 0;
                        ?>
                        <tr style="<?= $isLow ? 'background:var(--danger-bg);' : '' ?>">
                            <td>
                                <code><?= e($p['sku'] ?: 'N/A') ?></code>
                            </td>
                            <td>
                                <strong style="color:var(--text-primary);"><?= e($p['product_name']) ?></strong>
                                <div style="font-size:0.72rem;color:var(--text-muted);">
                                    <?= e($p['brand']) ?> <?= e($p['model']) ?> &bull; Supplier: <?= e($p['supplier_name'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-navy"><?= e($p['category_name'] ?: 'General') ?></span>
                            </td>
                            <td><?= formatCurrency($p['purchase_price']) ?></td>
                            <td>
                                <strong style="color:var(--blue-primary);font-size:0.95rem;"><?= formatCurrency($p['selling_price']) ?></strong>
                            </td>
                            <td>
                                <span style="font-size:0.75rem;color:var(--success);font-weight:700;">
                                    +<?= formatCurrency($margin) ?> (<?= $marginPercent ?>%)
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $isLow ? 'badge-danger' : 'badge-success' ?>" style="font-size:0.8rem;padding:0.25rem 0.65rem;">
                                    <?= $p['stock_quantity'] ?> <?= $isLow ? '&#9888; LOW' : 'in stock' ?>
                                </span>
                            </td>
                            <td style="color:var(--text-muted);font-size:0.8rem;"><?= $p['minimum_stock'] ?> units</td>
                            <?php if (hasRole(['admin'])): ?>
                                <td style="text-align:right;">
                                    <div class="btn-group">
                                        <a href="<?= baseUrl('products/stock.php?product_id=' . $p['product_id']) ?>" class="btn btn-outline btn-sm" title="Adjust Stock">
                                            &#128259;
                                        </a>
                                        <a href="<?= baseUrl('products/edit.php?id=' . $p['product_id']) ?>" class="btn btn-outline btn-sm" title="Edit Product">
                                            &#9998;
                                        </a>
                                        <form method="POST" action="<?= baseUrl('products/index.php') ?>" style="display:inline;" onsubmit="return confirm('Delete <?= e($p['product_name']) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                &#128465;
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
