<?php
/**
 * MUKIL TECH - Supplier Profile & Products
 */

$pageTitle = 'Supplier Profile';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    flashMessage("Supplier not found.", 'danger');
    header("Location: " . baseUrl('suppliers/index.php'));
    exit;
}

// Fetch products from this supplier
$stmt = $db->prepare("SELECT p.*, c.category_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.category_id 
                      WHERE p.supplier_id = ? 
                      ORDER BY p.product_name ASC");
$stmt->execute([$id]);
$products = $stmt->fetchAll();

// Fetch purchases from this supplier
$stmt = $db->prepare("SELECT * FROM purchases WHERE supplier_id = ? ORDER BY purchase_id DESC");
$stmt->execute([$id]);
$purchases = $stmt->fetchAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <a href="<?= baseUrl('suppliers/index.php') ?>" class="btn btn-outline btn-sm">&larr; Back to Suppliers</a>
    </div>
    <div style="display:flex;gap:0.5rem;">
        <a href="<?= baseUrl('products/add.php?supplier_id=' . $supplier['supplier_id']) ?>" class="btn btn-primary btn-sm">
            <span>+</span> Add Product for this Supplier
        </a>
        <a href="<?= baseUrl('suppliers/edit.php?id=' . $supplier['supplier_id']) ?>" class="btn btn-outline btn-sm">
            <span>&#9998;</span> Edit Supplier
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.75rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Supplier #<?= $supplier['supplier_id'] ?></div>
            <h2 style="font-size:1.4rem;color:var(--navy-main);font-weight:800;"><?= e($supplier['company_name']) ?></h2>
            <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:0.2rem;">
                Contact: <strong><?= e($supplier['supplier_name']) ?></strong> &bull; 
                Phone: <strong><?= e($supplier['phone'] ?: '-') ?></strong> &bull; 
                Email: <strong><?= e($supplier['email'] ?: '-') ?></strong>
            </div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">
                &#128205; <?= e($supplier['address'] ?: 'No address specified') ?>
            </div>
        </div>

        <div style="display:flex;gap:1.5rem;background:#f8fafc;padding:0.75rem 1.25rem;border-radius:var(--radius-sm);border:1px solid var(--border-light);">
            <div>
                <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Linked Products</div>
                <div style="font-size:1.2rem;font-weight:800;color:var(--blue-primary);"><?= count($products) ?> Items</div>
            </div>
            <div style="border-left:1px solid var(--border-medium);padding-left:1.5rem;">
                <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Purchase Orders</div>
                <div style="font-size:1.2rem;font-weight:800;color:var(--navy-main);"><?= count($purchases) ?> Records</div>
            </div>
        </div>
    </div>
</div>

<!-- Products from this supplier -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span>&#128230;</span> Products Supplied (<?= count($products) ?>)
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Purchase Price</th>
                    <th>Selling Price</th>
                    <th>In Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No products registered under this supplier yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><code><?= e($p['sku']) ?></code></td>
                            <td>
                                <strong><?= e($p['product_name']) ?></strong>
                                <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($p['brand']) ?> <?= e($p['model']) ?></div>
                            </td>
                            <td><?= e($p['category_name'] ?: 'Uncategorized') ?></td>
                            <td><?= formatCurrency($p['purchase_price']) ?></td>
                            <td><strong style="color:var(--blue-primary);"><?= formatCurrency($p['selling_price']) ?></strong></td>
                            <td>
                                <span class="badge <?= $p['stock_quantity'] <= $p['minimum_stock'] ? 'badge-danger' : 'badge-success' ?>">
                                    <?= $p['stock_quantity'] ?> in stock
                                </span>
                            </td>
                            <td>
                                <a href="<?= baseUrl('products/edit.php?id=' . $p['product_id']) ?>" class="btn btn-outline btn-sm">
                                    Edit
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
