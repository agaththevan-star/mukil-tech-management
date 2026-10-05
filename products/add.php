<?php
/**
 * MUKIL TECH - Add New Product / Spare Part
 */

$pageTitle = 'Add New Product';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name     = trim($_POST['product_name'] ?? '');
    $catId    = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $brand    = trim($_POST['brand'] ?? '');
    $model    = trim($_POST['model'] ?? '');
    $sku      = trim($_POST['sku'] ?? '');
    $purchase = (float)($_POST['purchase_price'] ?? 0);
    $selling  = (float)($_POST['selling_price'] ?? 0);
    $stock    = (int)($_POST['stock_quantity'] ?? 0);
    $minStock = (int)($_POST['minimum_stock'] ?? 5);
    $suppId   = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

    if (empty($name) || $selling <= 0) {
        $error = 'Product name and a valid selling price are required.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO products (product_name, category_id, brand, model, sku, purchase_price, selling_price, stock_quantity, minimum_stock, supplier_id) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $catId, $brand, $model, $sku, $purchase, $selling, $stock, $minStock, $suppId]);
            $newId = $db->lastInsertId();

            // Record initial stock movement if quantity > 0
            if ($stock > 0) {
                recordStockMovement($newId, 'PURCHASE', $stock, 'INIT-STOCK', 'Initial inventory stock intake');
            }

            flashMessage("Product '{$name}' created successfully.", 'success');
            header("Location: " . baseUrl('products/index.php'));
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$suppliers  = $db->query("SELECT * FROM suppliers ORDER BY company_name ASC")->fetchAll();
?>

<div style="max-width:800px;margin:0 auto;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>&#128230;</span> New Product / Accessories Form
            </div>
            <a href="<?= baseUrl('products/index.php') ?>" class="btn btn-outline btn-sm">&larr; Back to Inventory</a>
        </div>

        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span class="alert-icon">&#9888;</span>
                    <span class="alert-content"><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="product_name">Product Name / Description <span class="required">*</span></label>
                    <input type="text" id="product_name" name="product_name" class="form-control" placeholder="e.g. 512GB NVMe M.2 SSD" required autofocus value="<?= e($_POST['product_name'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="category_id">Category</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                        <?= e($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="supplier_id">Supplier</label>
                            <select id="supplier_id" name="supplier_id" class="form-control">
                                <option value="">-- Select Supplier --</option>
                                <?php foreach ($suppliers as $supp): ?>
                                    <option value="<?= $supp['supplier_id'] ?>" <?= (isset($_GET['supplier_id']) && $_GET['supplier_id'] == $supp['supplier_id']) || (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supp['supplier_id']) ? 'selected' : '' ?>>
                                        <?= e($supp['company_name']) ?> (<?= e($supp['supplier_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="brand">Brand</label>
                            <input type="text" id="brand" name="brand" class="form-control" placeholder="e.g. Kingston / Corsair / Logitech" value="<?= e($_POST['brand'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="model">Model / Spec</label>
                            <input type="text" id="model" name="model" class="form-control" placeholder="e.g. NV2 Gen4 / Fury Beast" value="<?= e($_POST['model'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="sku">SKU / Barcode</label>
                            <input type="text" id="sku" name="sku" class="form-control" placeholder="e.g. SSD-KNG-512" value="<?= e($_POST['sku'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row" style="background:#f8fafc;padding:1rem;border-radius:var(--radius-sm);margin-bottom:1rem;border:1px solid var(--border-light);">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="purchase_price">Purchase Cost (Rs.)</label>
                            <input type="number" step="0.01" id="purchase_price" name="purchase_price" class="form-control" placeholder="0.00" value="<?= e($_POST['purchase_price'] ?? '0.00') ?>">
                            <div class="form-hint">Cost paid to supplier</div>
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="selling_price">Selling Price (Rs.) <span class="required">*</span></label>
                            <input type="number" step="0.01" id="selling_price" name="selling_price" class="form-control" placeholder="0.00" required value="<?= e($_POST['selling_price'] ?? '') ?>">
                            <div class="form-hint">Retail / repair replacement price</div>
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="stock_quantity">Opening Stock (Units)</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" placeholder="0" value="<?= e($_POST['stock_quantity'] ?? '10') ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="minimum_stock">Min. Stock Alert Level</label>
                            <input type="number" id="minimum_stock" name="minimum_stock" class="form-control" placeholder="5" value="<?= e($_POST['minimum_stock'] ?? '5') ?>">
                        </div>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;">
                    <a href="<?= baseUrl('products/index.php') ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <span>&#128190;</span> Save Product to Inventory
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
