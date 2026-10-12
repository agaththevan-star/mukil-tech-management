<?php
/**
 * MUKIL TECH - POS Billing Terminal
 */

$pageTitle = 'POS Billing Terminal';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'cashier']);

$db = getDB();
$error = '';

// Handle POS Checkout POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    verifyCsrfToken();

    $customerId = (int)($_POST['customer_id'] ?? 5); // default walk-in
    $itemsJson  = $_POST['cart_items_json'] ?? '[]';
    $cartItems  = json_decode($itemsJson, true);

    $overallDiscount = max(0, (float)($_POST['overall_discount'] ?? 0));
    $paymentMethod   = $_POST['payment_method'] ?? 'Cash';
    $paidAmount      = max(0, (float)($_POST['paid_amount'] ?? 0));
    $notes           = trim($_POST['notes'] ?? '');

    if (empty($cartItems) || !is_array($cartItems)) {
        $error = 'Your cart is empty. Please select products to complete the sale.';
    } else {
        try {
            $db->beginTransaction();

            $subtotal = 0;
            $requested = [];
            $productStmt = $db->prepare('SELECT product_name, selling_price, stock_quantity FROM products WHERE product_id = ? FOR UPDATE');
            foreach ($cartItems as &$it) {
                if (!is_array($it) || !filter_var($it['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) || !filter_var($it['qty'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                    throw new RuntimeException('Invalid cart item.');
                }
                $productStmt->execute([$it['id']]);
                $product = $productStmt->fetch();
                $requested[$it['id']] = ($requested[$it['id']] ?? 0) + (int)$it['qty'];
                if (!$product || $requested[$it['id']] > (int)$product['stock_quantity']) {
                    throw new RuntimeException('Product unavailable or insufficient stock.');
                }
                $it['name'] = $product['product_name'];
                $it['price'] = (float)$product['selling_price'];
                $it['discount'] = (float)($it['discount'] ?? 0);
                if (!is_finite($it['discount']) || $it['discount'] < 0 || $it['discount'] > $it['price'] * $it['qty']) {
                    throw new RuntimeException('Invalid discount.');
                }
                $subtotal += ($it['price'] * $it['qty']) - ($it['discount'] ?? 0);
            }
            unset($it);
            if (!is_finite($overallDiscount) || !is_finite($paidAmount) || $overallDiscount > $subtotal) {
                throw new RuntimeException('Invalid payment or discount.');
            }
            $grandTotal = max(0, $subtotal - $overallDiscount);
            $balance = max(0, $grandTotal - $paidAmount);
            $invoiceNo = generateInvoiceNo();
            $saleStatus = ($balance <= 0) ? 'Completed' : 'Pending';

            // 1. Insert Sales
            $stmt = $db->prepare("INSERT INTO sales (invoice_no, customer_id, user_id, subtotal, discount, grand_total, paid_amount, balance, payment_method, sale_date, status, notes) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)");
            $stmt->execute([
                $invoiceNo,
                $customerId,
                $currentUser['id'],
                $subtotal,
                $overallDiscount,
                $grandTotal,
                $paidAmount,
                $balance,
                $paymentMethod,
                $saleStatus,
                $notes
            ]);
            $saleId = $db->lastInsertId();

            // 2. Insert Invoices table record
            $invStatus = ($balance <= 0) ? 'Paid' : (($paidAmount > 0) ? 'Partial' : 'Unpaid');
            $stmtInv = $db->prepare("INSERT INTO invoices (invoice_no, type, reference_id, customer_id, date, subtotal, discount, total, paid, balance, payment_method, status) 
                                     VALUES (?, 'sale', ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?)");
            $stmtInv->execute([
                $invoiceNo,
                $saleId,
                $customerId,
                $subtotal,
                $overallDiscount,
                $grandTotal,
                $paidAmount,
                $balance,
                $paymentMethod,
                $invStatus
            ]);
            $invoiceId = $db->lastInsertId();

            // 3. Insert Sale Items, Invoice Items, & Deduct Stock
            $stmtItem = $db->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, discount, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtInvItem = $db->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, discount, total) VALUES (?, ?, ?, ?, ?, ?)");

            foreach ($cartItems as $it) {
                $pId = (int)$it['id'];
                $pName = $it['name'];
                $qty = (int)$it['qty'];
                $uPrice = (float)$it['price'];
                $iDisc = (float)($it['discount'] ?? 0);
                $rowTotal = ($uPrice * $qty) - $iDisc;

                $stmtItem->execute([$saleId, $pId, $pName, $qty, $uPrice, $iDisc, $rowTotal]);
                $stmtInvItem->execute([$invoiceId, $pName, $qty, $uPrice, $iDisc, $rowTotal]);

                // Deduct stock and log movement
                if (!recordStockMovement($pId, 'SALE', -$qty, $invoiceNo, "POS Sale Invoice #$invoiceNo")) {
                    throw new RuntimeException('Stock update failed.');
                }
            }

            // 4. Log Payment if paid > 0
            if ($paidAmount > 0) {
                $payStmt = $db->prepare("INSERT INTO payments (invoice_no, sale_id, customer_id, amount, payment_method, payment_date, received_by, notes) 
                                         VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?)");
                $payStmt->execute([
                    $invoiceNo,
                    $saleId,
                    $customerId,
                    $paidAmount,
                    $paymentMethod,
                    $currentUser['full_name'],
                    "POS Checkout payment"
                ]);
            }

            $db->commit();

            flashMessage("Sale completed! Invoice #{$invoiceNo} generated.", 'success');
            header("Location: " . baseUrl("sales/invoice.php?no=" . urlencode($invoiceNo)));
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = logException($e, 'Error processing transaction due to an internal error.');
        }
    }
}

// Fetch all available products with stock > 0
$products = $db->query("SELECT p.*, c.category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        ORDER BY p.product_name ASC")->fetchAll();

// Fetch categories for filtering
$categories = $db->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

// Fetch customers
$customers = $db->query("SELECT * FROM customers ORDER BY customer_name ASC")->fetchAll();
$selectedCustId = (int)($_GET['customer_id'] ?? 5); // default walk-in
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <span class="alert-icon">&#9888;</span>
        <span class="alert-content"><?= e($error) ?></span>
    </div>
<?php endif; ?>

<div class="pos-container">
    <!-- Left Column: Product Selection & Catalog -->
    <div class="pos-products-col">
        <div class="pos-filter-bar">
            <div style="display:flex;gap:0.75rem;align-items:center;">
                <div class="search-box" style="flex:1;max-width:100%;">
                    <span class="search-icon">&#128269;</span>
                    <input type="text" id="posSearch" class="form-control" placeholder="Search by name, brand, SKU or barcode..." oninput="filterPosProducts()" autofocus>
                </div>
            </div>

            <!-- Category Pills -->
            <div class="pos-category-pills">
                <button type="button" class="cat-pill active" onclick="setPosCat(0, this)">All Items</button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="cat-pill" onclick="setPosCat(<?= $cat['category_id'] ?>, this)">
                        <?= e($cat['category_name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pos-grid" id="posGrid">
            <?php foreach ($products as $p): ?>
                <div class="pos-item-card" 
                     data-id="<?= $p['product_id'] ?>" 
                     data-name="<?= e($p['product_name']) ?>" 
                     data-price="<?= $p['selling_price'] ?>" 
                     data-stock="<?= $p['stock_quantity'] ?>" 
                     data-sku="<?= e($p['sku'] ?? '') ?>" 
                     data-brand="<?= e($p['brand'] ?? '') ?>" 
                     data-cat="<?= $p['category_id'] ?? 0 ?>"
                     onclick="addToCart(Number(this.dataset.id), this.dataset.name, Number(this.dataset.price), Number(this.dataset.stock))">
                    
                    <div>
                        <div class="pos-item-brand"><?= e($p['brand'] ?: 'Hardware') ?></div>
                        <div class="pos-item-title"><?= e($p['product_name']) ?></div>
                    </div>

                    <div class="pos-item-footer">
                        <div class="pos-item-price"><?= formatCurrency($p['selling_price']) ?></div>
                        <div class="pos-item-stock" style="<?= $p['stock_quantity'] <= $p['minimum_stock'] ? 'color:var(--danger);font-weight:700;' : '' ?>">
                            <?= $p['stock_quantity'] ?> in stock
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right Column: Cart & Billing Form -->
    <div class="pos-cart-col">
        <form method="POST" id="posForm">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="checkout">
            <input type="hidden" name="cart_items_json" id="cart_items_json">

            <div class="pos-cart-header">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                    <div style="font-weight:700;color:var(--navy-main);font-size:1rem;">
                        <span>&#128722;</span> Current Bill
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="clearCart()" style="font-size:0.75rem;padding:0.2rem 0.5rem;">
                        Clear Cart
                    </button>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <select name="customer_id" class="form-control" style="font-size:0.85rem;" required>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['customer_id'] ?>" <?= $selectedCustId === $c['customer_id'] ? 'selected' : '' ?>>
                                    <?= e($c['customer_name']) ?> (<?= e($c['phone'] ?: 'No phone') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= baseUrl('customers/add.php?redirect=sales/pos.php') ?>" class="btn btn-outline btn-sm" title="Register New Customer" style="white-space:nowrap;">
                            + New
                        </a>
                    </div>
                </div>
            </div>

            <!-- Cart Table Items -->
            <div class="pos-cart-items" id="cartContainer">
                <div id="emptyCartNotice" style="text-align:center;padding:3rem 1rem;color:var(--text-muted);font-size:0.88rem;">
                    <div style="font-size:2.5rem;margin-bottom:0.5rem;opacity:0.4;">&#128722;</div>
                    Cart is empty.<br>Click any product on the left to add it to the bill.
                </div>
                <table class="table table-compact" id="cartTable" style="display:none;width:100%;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="width:75px;text-align:center;">Qty</th>
                            <th style="width:80px;text-align:right;">Price</th>
                            <th style="width:75px;text-align:right;">Total</th>
                            <th style="width:30px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody"></tbody>
                </table>
            </div>

            <!-- Summary & Payment Block -->
            <div class="pos-cart-summary">
                <div class="summary-line">
                    <span>Subtotal:</span>
                    <span id="txtSubtotal">Rs. 0.00</span>
                </div>

                <div class="summary-line" style="align-items:center;">
                    <span>Overall Discount:</span>
                    <div style="display:flex;align-items:center;gap:0.3rem;">
                        <span>Rs.</span>
                        <input type="number" step="0.01" name="overall_discount" id="overall_discount" value="0.00" class="form-control" style="width:90px;padding:0.25rem 0.5rem;text-align:right;font-size:0.85rem;" oninput="recalcTotals()">
                    </div>
                </div>

                <div class="summary-line summary-total">
                    <span>Grand Total:</span>
                    <span id="txtGrandTotal" style="color:var(--blue-primary);">Rs. 0.00</span>
                </div>

                <div class="form-row" style="margin-top:0.75rem;">
                    <div class="form-col" style="min-width:140px;">
                        <label class="form-label" style="font-size:0.75rem;">Payment Method</label>
                        <select name="payment_method" class="form-control" style="padding:0.4rem 0.6rem;font-size:0.82rem;">
                            <option value="Cash" selected>Cash</option>
                            <option value="Card">Credit/Debit Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-col" style="min-width:140px;">
                        <label class="form-label" style="font-size:0.75rem;">Amount Received (Rs.)</label>
                        <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control" style="padding:0.4rem 0.6rem;font-size:0.88rem;font-weight:700;" placeholder="0.00" oninput="recalcTotals()" required>
                    </div>
                </div>

                <div class="summary-line" style="margin-top:0.6rem;font-weight:700;">
                    <span>Change / Balance:</span>
                    <span id="txtBalance" style="color:var(--success);">Rs. 0.00</span>
                </div>

                <button type="submit" id="btnCheckout" class="btn btn-primary" style="width:100%;margin-top:0.85rem;padding:0.75rem;font-size:1rem;" disabled>
                    <span>&#10003;</span> Complete Sale & Print Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Basic Vanilla JavaScript POS State
var cart = [];
var selectedCatId = 0;

function setPosCat(catId, btn) {
    selectedCatId = catId;
    document.querySelectorAll('.cat-pill').forEach(function(el) { el.classList.remove('active'); });
    btn.classList.add('active');
    filterPosProducts();
}

function filterPosProducts() {
    var search = document.getElementById('posSearch').value.toLowerCase().trim();
    var cards = document.querySelectorAll('.pos-item-card');

    cards.forEach(function(card) {
        var name = (card.getAttribute('data-name') || '').toLowerCase();
        var sku = (card.getAttribute('data-sku') || '').toLowerCase();
        var brand = (card.getAttribute('data-brand') || '').toLowerCase();
        var cat = parseInt(card.getAttribute('data-cat') || 0);

        var matchCat = (selectedCatId === 0 || cat === selectedCatId);
        var matchSearch = (!search || name.indexOf(search) !== -1 || sku.indexOf(search) !== -1 || brand.indexOf(search) !== -1);

        if (matchCat && matchSearch) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function addToCart(id, name, price, stock) {
    var existing = cart.find(function(item) { return item.id === id; });
    if (existing) {
        if (existing.qty < stock) {
            existing.qty += 1;
        } else {
            alert("Maximum stock reached (" + stock + " units available).");
            return;
        }
    } else {
        if (stock <= 0) {
            if (!confirm("This item is currently out of stock. Add anyway?")) return;
        }
        cart.push({
            id: id,
            name: name,
            price: parseFloat(price),
            qty: 1,
            discount: 0,
            maxStock: stock
        });
    }
    renderCart();
}

function updateQty(id, delta) {
    var item = cart.find(function(i) { return i.id === id; });
    if (!item) return;
    var newQty = item.qty + delta;
    if (newQty <= 0) {
        removeFromCart(id);
    } else if (newQty > item.maxStock) {
        alert("Cannot exceed available stock of " + item.maxStock);
    } else {
        item.qty = newQty;
        renderCart();
    }
}

function removeFromCart(id) {
    cart = cart.filter(function(i) { return i.id !== id; });
    renderCart();
}

function clearCart() {
    if (cart.length > 0 && confirm("Are you sure you want to clear the bill?")) {
        cart = [];
        renderCart();
    }
}

function renderCart() {
    var emptyNotice = document.getElementById('emptyCartNotice');
    var table = document.getElementById('cartTable');
    var tbody = document.getElementById('cartTableBody');
    var btn = document.getElementById('btnCheckout');

    if (cart.length === 0) {
        emptyNotice.style.display = 'block';
        table.style.display = 'none';
        btn.disabled = true;
    } else {
        emptyNotice.style.display = 'none';
        table.style.display = 'table';
        btn.disabled = false;
    }

    tbody.innerHTML = '';
    cart.forEach(function(item) {
        var tr = document.createElement('tr');
        var rowTotal = (item.price * item.qty) - item.discount;
        tr.innerHTML = `
            <td>
                <div class="cart-product-name" style="font-weight:600;color:var(--navy-main);"></div>
                <div style="font-size:0.72rem;color:var(--text-muted);">Rs. ${item.price.toFixed(2)} each</div>
            </td>
            <td style="text-align:center;">
                <div style="display:inline-flex;align-items:center;gap:3px;">
                    <button type="button" class="btn btn-outline btn-sm" style="padding:1px 6px;font-size:0.75rem;" onclick="updateQty(${item.id}, -1)">-</button>
                    <span style="font-weight:700;font-size:0.85rem;min-width:18px;text-align:center;">${item.qty}</span>
                    <button type="button" class="btn btn-outline btn-sm" style="padding:1px 6px;font-size:0.75rem;" onclick="updateQty(${item.id}, 1)">+</button>
                </div>
            </td>
            <td style="text-align:right;">Rs. ${item.price.toFixed(2)}</td>
            <td style="text-align:right;font-weight:700;color:var(--navy-main);">Rs. ${rowTotal.toFixed(2)}</td>
            <td>
                <button type="button" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:0.9rem;" onclick="removeFromCart(${item.id})" title="Remove item">&times;</button>
            </td>
        `;
        tr.querySelector('.cart-product-name').textContent = item.name;
        tbody.appendChild(tr);
    });

    document.getElementById('cart_items_json').value = JSON.stringify(cart);
    recalcTotals();
}

function recalcTotals() {
    var subtotal = 0;
    cart.forEach(function(i) {
        subtotal += (i.price * i.qty) - (i.discount || 0);
    });

    var overallDisc = parseFloat(document.getElementById('overall_discount').value) || 0;
    var grandTotal = Math.max(0, subtotal - overallDisc);

    var paidInput = document.getElementById('paid_amount');
    // If user hasn't typed anything yet, auto-fill full amount for quick 1-click cash checkout
    if (paidInput.value === '' && grandTotal > 0) {
        paidInput.value = grandTotal.toFixed(2);
    }
    var paid = parseFloat(paidInput.value) || 0;
    var balanceOrChange = paid - grandTotal;

    document.getElementById('txtSubtotal').textContent = 'Rs. ' + subtotal.toFixed(2);
    document.getElementById('txtGrandTotal').textContent = 'Rs. ' + grandTotal.toFixed(2);

    var balElem = document.getElementById('txtBalance');
    if (balanceOrChange >= 0) {
        balElem.style.color = 'var(--success)';
        balElem.textContent = 'Change: Rs. ' + balanceOrChange.toFixed(2);
    } else {
        balElem.style.color = 'var(--danger)';
        balElem.textContent = 'Balance Due: Rs. ' + Math.abs(balanceOrChange).toFixed(2);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
