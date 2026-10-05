<?php
/**
 * MUKIL TECH - Inventory Stock Adjustment & Movement History
 */

$pageTitle = 'Stock Movements & Inventory Adjustments';

// Load auth and helpers early so POST handling can redirect before any output
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$db = getDB();
$selectedProdId = (int)($_GET['product_id'] ?? 0);

// Handle Stock Adjustment POST
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust') {
    verifyCsrfToken();

    $prodId   = (int)$_POST['product_id'];
    $mType    = $_POST['movement_type'] ?? 'ADJUSTMENT';
    $mode     = $_POST['adjust_mode'] ?? 'add'; // 'add', 'deduct', 'set'
    $qtyInput = (int)$_POST['quantity'];
    $refId    = trim($_POST['reference_id'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');

    // Get current stock
    $stmt = $db->prepare("SELECT stock_quantity, product_name FROM products WHERE product_id = ?");
    $stmt->execute([$prodId]);
    $prod = $stmt->fetch();

    if (!$prod) {
        $error = 'Product not found.';
    } elseif ($qtyInput <= 0 && $mode !== 'set') {
        $error = 'Please specify a positive quantity.';
    } else {
        $prevStock = (int)$prod['stock_quantity'];
        if ($mode === 'add') {
            $change = $qtyInput;
            $newStock = $prevStock + $qtyInput;
        } elseif ($mode === 'deduct') {
            $change = -$qtyInput;
            $newStock = max(0, $prevStock - $qtyInput);
        } else { // 'set'
            $newStock = max(0, $qtyInput);
            $change = $newStock - $prevStock;
        }

        try {
            $up = $db->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
            $up->execute([$newStock, $prodId]);

            $ins = $db->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, current_stock, reference_id, notes) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$prodId, $mType, $change, $prevStock, $newStock, $refId, $notes]);

            flashMessage("Stock updated for '{$prod['product_name']}'. Current stock is now {$newStock}.", 'success');
            header("Location: " . baseUrl("products/stock.php?product_id=$prodId"));
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch all products for select
$allProducts = $db->query("SELECT product_id, product_name, sku, stock_quantity FROM products ORDER BY product_name ASC")->fetchAll();

// Fetch recent stock movements
$movementQuery = "SELECT sm.*, p.product_name, p.sku, p.brand 
                  FROM stock_movements sm 
                  JOIN products p ON sm.product_id = p.product_id ";
$params = [];
if ($selectedProdId > 0) {
    $movementQuery .= "WHERE sm.product_id = ? ";
    $params[] = $selectedProdId;
}
$movementQuery .= "ORDER BY sm.movement_id DESC LIMIT 100";

$stmt = $db->prepare($movementQuery);
$stmt->execute($params);
$movements = $stmt->fetchAll();
// Include header after POST handling and DB queries
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <span class="alert-icon">&#9888;</span>
        <span class="alert-content"><?= e($error) ?></span>
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:380px 1fr;gap:1.5rem;align-items:start;">
    <!-- Stock Adjustment Form Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>&#128259;</span> Stock Adjustment Form
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="adjust">

                <div class="form-group">
                    <label class="form-label" for="product_id">Select Product <span class="required">*</span></label>
                    <select id="product_id" name="product_id" class="form-control" required onchange="window.location='<?= baseUrl('products/stock.php') ?>?product_id=' + this.value">
                        <option value="">-- Choose Product --</option>
                        <?php foreach ($allProducts as $p): ?>
                            <option value="<?= $p['product_id'] ?>" <?= $selectedProdId === (int)$p['product_id'] ? 'selected' : '' ?>>
                                <?= e($p['product_name']) ?> (Stock: <?= $p['stock_quantity'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Adjustment Mode <span class="required">*</span></label>
                    <div style="display:flex;gap:0.75rem;margin-top:0.25rem;">
                        <label style="display:flex;align-items:center;gap:0.3rem;font-size:0.85rem;cursor:pointer;">
                            <input type="radio" name="adjust_mode" value="add" checked> + Add Stock
                        </label>
                        <label style="display:flex;align-items:center;gap:0.3rem;font-size:0.85rem;cursor:pointer;">
                            <input type="radio" name="adjust_mode" value="deduct"> - Deduct Stock
                        </label>
                        <label style="display:flex;align-items:center;gap:0.3rem;font-size:0.85rem;cursor:pointer;">
                            <input type="radio" name="adjust_mode" value="set"> = Set Exact
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="movement_type">Reason / Movement Type <span class="required">*</span></label>
                    <select id="movement_type" name="movement_type" class="form-control" required>
                        <option value="PURCHASE">PURCHASE (New Shipment Received)</option>
                        <option value="ADJUSTMENT" selected>ADJUSTMENT (Audit / Correction)</option>
                        <option value="RETURN">RETURN (Customer Return / RMA)</option>
                        <option value="REPAIR">REPAIR (Used in PC Service)</option>
                        <option value="SALE">SALE (Sold to Customer)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity <span class="required">*</span></label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="0" placeholder="e.g. 5" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reference_id">Reference ID / Invoice #</label>
                    <input type="text" id="reference_id" name="reference_id" class="form-control" placeholder="e.g. PO-8921 or ADJ-01">
                </div>

                <div class="form-group">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="e.g. Supplier delivery, defective return, physical count verification"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    Submit Stock Update
                </button>
            </form>
        </div>
    </div>

    <!-- Stock Movement History Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>&#128220;</span> Stock Audit & Movement History (<?= count($movements) ?>)
            </div>
            <?php if ($selectedProdId > 0): ?>
                <a href="<?= baseUrl('products/stock.php') ?>" class="btn btn-outline btn-sm">Show All Products</a>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Change</th>
                        <th>Prev &rarr; New</th>
                        <th>Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movements)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:2.5rem;color:var(--text-muted);">No stock movements recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movements as $m): ?>
                            <tr>
                                <td style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap;">
                                    <?= formatDate($m['created_at'], true) ?>
                                </td>
                                <td>
                                    <strong><?= e($m['product_name']) ?></strong>
                                    <div style="font-size:0.7rem;color:var(--text-muted);"><?= e($m['sku'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <?php
                                        $typeBadge = 'badge-navy';
                                        if ($m['movement_type'] === 'SALE') $typeBadge = 'badge-primary';
                                        if ($m['movement_type'] === 'REPAIR') $typeBadge = 'badge-warning';
                                        if ($m['movement_type'] === 'PURCHASE') $typeBadge = 'badge-success';
                                        if ($m['movement_type'] === 'RETURN') $typeBadge = 'badge-navy';
                                    ?>
                                    <span class="badge <?= $typeBadge ?>">
                                        <?= e($m['movement_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($m['quantity'] > 0): ?>
                                        <strong style="color:var(--success);">+<?= $m['quantity'] ?></strong>
                                    <?php elseif ($m['quantity'] < 0): ?>
                                        <strong style="color:var(--danger);"><?= $m['quantity'] ?></strong>
                                    <?php else: ?>
                                        <span>0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color:var(--text-muted);"><?= $m['previous_stock'] ?></span>
                                    &rarr;
                                    <strong><?= $m['current_stock'] ?></strong>
                                </td>
                                <td>
                                    <code><?= e($m['reference_id'] ?: '-') ?></code>
                                </td>
                                <td style="font-size:0.78rem;color:var(--text-secondary);max-width:180px;">
                                    <?= e($m['notes'] ?: '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
