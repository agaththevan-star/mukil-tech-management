<?php
/**
 * MUKIL TECH - PC & Laptop Repair Job Control Center
 */

$pageTitle = 'Manage Repair Job';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'technician']);

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// Helper function to recalculate repair costs
function recalculateRepairTotals($repairId, $db) {
    // Sum parts
    $partsStmt = $db->prepare("SELECT COALESCE(SUM(total), 0) FROM repair_parts WHERE repair_id = ?");
    $partsStmt->execute([$repairId]);
    $partsCost = (float)$partsStmt->fetchColumn();

    // Fetch existing labour, other, discount, advance
    $rStmt = $db->prepare("SELECT labour_cost, other_charges, discount, advance_payment FROM repairs WHERE repair_id = ?");
    $rStmt->execute([$repairId]);
    $r = $rStmt->fetch();

    $labourCost = (float)$r['labour_cost'];
    $otherCharges = (float)$r['other_charges'];
    $discount = (float)$r['discount'];
    $advance = (float)$r['advance_payment'];

    $finalCost = max(0, ($partsCost + $labourCost + $otherCharges) - $discount);
    $balance = max(0, $finalCost - $advance);

    $up = $db->prepare("UPDATE repairs SET parts_cost = ?, final_cost = ?, balance = ? WHERE repair_id = ?");
    $up->execute([$partsCost, $finalCost, $balance, $repairId]);
}

// 1. Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    verifyCsrfToken();
    $newStatus = $_POST['status'] ?? 'Received';
    $techNotes = trim($_POST['repair_notes'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $completedDate = in_array($newStatus, ['Ready', 'Delivered']) ? date('Y-m-d') : null;

    $stmt = $db->prepare("UPDATE repairs SET status = ?, repair_notes = ?, diagnosis = ?, completed_date = COALESCE(?, completed_date) WHERE repair_id = ?");
    $stmt->execute([$newStatus, $techNotes, $diagnosis, $completedDate, $id]);

    flashMessage("Repair status changed to '{$newStatus}'.", 'success');
    header("Location: " . baseUrl("repairs/view.php?id=$id"));
    exit;
}

// 2. Handle Add Part Used from Inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_part') {
    verifyCsrfToken();
    $productId = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $customPartName = trim($_POST['custom_part_name'] ?? '');
    $qty = max(1, (int)$_POST['quantity']);
    $price = (float)$_POST['unit_price'];
    $partName = '';

    if ($productId) {
        $pStmt = $db->prepare("SELECT product_name, stock_quantity FROM products WHERE product_id = ?");
        $pStmt->execute([$productId]);
        $prod = $pStmt->fetch();
        if ($prod) {
            $partName = $prod['product_name'];
            // Check stock
            if ($prod['stock_quantity'] < $qty) {
                flashMessage("Warning: Only {$prod['stock_quantity']} units in stock.", 'warning');
            }
            // Deduct stock
            recordStockMovement($productId, 'REPAIR', -$qty, "REP-JOB-$id", "Part used in repair job #$id");
        }
    } else {
        $partName = $customPartName ?: 'Replacement Hardware Part';
    }

    $rowTotal = $price * $qty;
    $insPart = $db->prepare("INSERT INTO repair_parts (repair_id, product_id, part_name, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
    $insPart->execute([$id, $productId, $partName, $qty, $price, $rowTotal]);

    recalculateRepairTotals($id, $db);

    flashMessage("Added '{$partName}' to repair bill.", 'success');
    header("Location: " . baseUrl("repairs/view.php?id=$id"));
    exit;
}

// 3. Handle Remove Part Used
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_part') {
    verifyCsrfToken();
    $partId = (int)$_POST['repair_part_id'];

    $ptStmt = $db->prepare("SELECT * FROM repair_parts WHERE id = ? AND repair_id = ?");
    $ptStmt->execute([$partId, $id]);
    $partRecord = $ptStmt->fetch();

    if ($partRecord) {
        // Return stock if it came from inventory
        if ($partRecord['product_id']) {
            recordStockMovement($partRecord['product_id'], 'RETURN', $partRecord['quantity'], "REP-RESTOCK-$id", "Removed from repair job #$id");
        }
        $delStmt = $db->prepare("DELETE FROM repair_parts WHERE id = ?");
        $delStmt->execute([$partId]);

        recalculateRepairTotals($id, $db);
        flashMessage("Part removed and stock restored.", 'success');
    }
    header("Location: " . baseUrl("repairs/view.php?id=$id"));
    exit;
}

// 4. Handle Cost Adjustments (Labour, Charges, Discount)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_charges') {
    verifyCsrfToken();
    $labour  = (float)($_POST['labour_cost'] ?? 0);
    $other   = (float)($_POST['other_charges'] ?? 0);
    $disc    = (float)($_POST['discount'] ?? 0);

    $upStmt = $db->prepare("UPDATE repairs SET labour_cost = ?, other_charges = ?, discount = ? WHERE repair_id = ?");
    $upStmt->execute([$labour, $other, $disc, $id]);

    recalculateRepairTotals($id, $db);

    flashMessage("Labour and pricing updated.", 'success');
    header("Location: " . baseUrl("repairs/view.php?id=$id"));
    exit;
}

// 5. Handle Record Balance / Settle Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    verifyCsrfToken();
    $amount = (float)($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'Cash';
    $notes  = trim($_POST['notes'] ?? 'Settlement payment');

    // Fetch current repair
    $stmt = $db->prepare("SELECT * FROM repairs WHERE repair_id = ?");
    $stmt->execute([$id]);
    $curRep = $stmt->fetch();

    if ($amount > 0 && $curRep) {
        $newAdv = $curRep['advance_payment'] + $amount;
        $newBal = max(0, $curRep['final_cost'] - $newAdv);
        $newStatus = ($newBal <= 0 && $curRep['status'] === 'Ready') ? 'Delivered' : $curRep['status'];

        $up = $db->prepare("UPDATE repairs SET advance_payment = ?, balance = ?, status = ? WHERE repair_id = ?");
        $up->execute([$newAdv, $newBal, $newStatus, $id]);

        $insPay = $db->prepare("INSERT INTO payments (invoice_no, repair_id, customer_id, amount, payment_method, payment_date, received_by, notes) 
                                VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?)");
        $insPay->execute([$curRep['repair_code'], $id, $curRep['customer_id'], $amount, $method, $currentUser['full_name'], $notes]);

        flashMessage("Collected payment of " . formatCurrency($amount) . ".", 'success');
    }
    header("Location: " . baseUrl("repairs/view.php?id=$id"));
    exit;
}

// Fetch repair details
$stmt = $db->prepare("SELECT r.*, c.customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address 
                      FROM repairs r 
                      LEFT JOIN customers c ON r.customer_id = c.customer_id 
                      WHERE r.repair_id = ?");
$stmt->execute([$id]);
$repair = $stmt->fetch();

if (!$repair) {
    flashMessage("Repair job not found.", 'danger');
    header("Location: " . baseUrl('repairs/index.php'));
    exit;
}

// Fetch parts used
$stmt = $db->prepare("SELECT * FROM repair_parts WHERE repair_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$partsUsed = $stmt->fetchAll();

// Fetch inventory products for add-part dropdown
$inventoryProducts = $db->query("SELECT product_id, product_name, brand, selling_price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY product_name ASC")->fetchAll();
?>

<!-- Action Bar -->
<div class="no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:0.75rem;">
    <a href="<?= baseUrl('repairs/index.php') ?>" class="btn btn-outline btn-sm">&larr; Back to Repair Queue</a>

    <div style="display:flex;gap:0.5rem;align-items:center;">
        <a href="<?= baseUrl('repairs/ticket.php?id=' . $repair['repair_id']) ?>" class="btn btn-outline btn-sm" target="_blank">
            <span>&#129534;</span> Print Intake Ticket
        </a>
        <a href="<?= baseUrl('repairs/invoice.php?id=' . $repair['repair_id']) ?>" class="btn btn-primary btn-sm" target="_blank">
            <span>&#128438;</span> Print Repair Bill
        </a>
    </div>
</div>

<!-- Main Job Banner Header -->
<div class="card">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <h2 style="font-size:1.5rem;font-weight:800;color:var(--navy-main);"><?= e($repair['repair_code']) ?></h2>
                <span class="badge badge-status-<?= str_replace(' ', '', $repair['status']) ?>" style="font-size:0.85rem;padding:0.35rem 0.85rem;">
                    <?= e($repair['status']) ?>
                </span>
            </div>
            <div style="font-size:1.1rem;font-weight:700;color:var(--blue-primary);margin-top:0.3rem;">
                <?= e($repair['brand']) ?> <?= e($repair['model']) ?> (<?= e($repair['device_type']) ?>)
            </div>
            <div style="font-size:0.82rem;color:var(--text-secondary);margin-top:0.25rem;">
                Serial / Tag: <code><?= e($repair['serial_number'] ?: 'N/A') ?></code> &bull; 
                Technician: <strong><?= e($repair['technician'] ?: 'Senior Tech') ?></strong>
            </div>
        </div>

        <div style="display:flex;gap:1.5rem;background:var(--bg-main);padding:0.75rem 1.25rem;border-radius:var(--radius-sm);border:1px solid var(--border-light);">
            <div>
                <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Total Repair Cost</div>
                <div style="font-size:1.25rem;font-weight:800;color:var(--text-primary);"><?= formatCurrency($repair['final_cost']) ?></div>
            </div>
            <div style="border-left:1px solid var(--border-medium);padding-left:1.5rem;">
                <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Paid / Advance</div>
                <div style="font-size:1.25rem;font-weight:800;color:var(--success);"><?= formatCurrency($repair['advance_payment']) ?></div>
            </div>
            <div style="border-left:1px solid var(--border-medium);padding-left:1.5rem;">
                <div style="font-size:0.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;">Balance Due</div>
                <div style="font-size:1.25rem;font-weight:800;color:<?= $repair['balance'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                    <?= formatCurrency($repair['balance']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.2fr;gap:1.5rem;align-items:start;">
    
    <!-- Left Column: Customer Details, Symptoms, Status Workflow -->
    <div>
        <!-- Customer Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>&#128100;</span> Customer Information
                </div>
                <a href="<?= baseUrl('customers/view.php?id=' . $repair['customer_id']) ?>" class="btn btn-outline btn-sm">Profile</a>
            </div>
            <div class="card-body">
                <div style="font-size:1.05rem;font-weight:700;color:var(--navy-main);"><?= e($repair['customer_name']) ?></div>
                <div style="margin-top:0.3rem;font-size:0.85rem;color:var(--text-secondary);">
                    <div>&#128222; <strong><?= e($repair['customer_phone']) ?></strong></div>
                    <?php if ($repair['customer_email']): ?><div>&#9993; <?= e($repair['customer_email']) ?></div><?php endif; ?>
                    <div>&#128205; <?= e($repair['customer_address'] ?: 'Kilinochchi') ?></div>
                </div>
            </div>
        </div>

        <!-- Problem & Technical Diagnosis -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>&#128203;</span> Problem & Diagnostics
                </div>
            </div>
            <div class="card-body">
                <div style="margin-bottom:1rem;">
                    <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);">Customer Problem Description</div>
                    <div style="background:var(--danger-bg);border-left:3px solid var(--danger);padding:0.6rem 0.85rem;border-radius:4px;font-size:0.85rem;margin-top:0.3rem;color:var(--text-primary);">
                        <?= nl2br(e($repair['problem_description'])) ?>
                    </div>
                </div>

                <div>
                    <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);">Technical Diagnosis</div>
                    <div style="background:var(--info-bg);border-left:3px solid var(--blue-primary);padding:0.6rem 0.85rem;border-radius:4px;font-size:0.85rem;margin-top:0.3rem;color:var(--text-primary);">
                        <?= $repair['diagnosis'] ? nl2br(e($repair['diagnosis'])) : 'Diagnosis in progress.' ?>
                    </div>
                </div>

                <?php if ($repair['repair_notes']): ?>
                    <div style="margin-top:1rem;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);">Service Notes</div>
                        <div style="background:var(--bg-main);padding:0.6rem 0.85rem;border-radius:4px;font-size:0.85rem;margin-top:0.3rem;border:1px solid var(--border-light);color:var(--text-primary);">
                            <?= nl2br(e($repair['repair_notes'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Workflow Form -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>&#128260;</span> Update Workflow Status
                </div>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="update_status">

                    <div class="form-group">
                        <label class="form-label" for="status">Job Status</label>
                        <select name="status" id="status" class="form-control" style="font-weight:700;color:var(--navy-main);">
                            <?php 
                            $options = ['Received', 'Diagnosing', 'Waiting for Parts', 'Repairing', 'Ready', 'Delivered', 'Cancelled'];
                            foreach ($options as $opt): ?>
                                <option value="<?= $opt ?>" <?= $repair['status'] === $opt ? 'selected' : '' ?>>
                                    <?= $opt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="diagnosis_up">Update Diagnosis</label>
                        <textarea name="diagnosis" id="diagnosis_up" class="form-control" rows="2" placeholder="Technical diagnostic findings"><?= e($repair['diagnosis']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="repair_notes">Service Notes / Work Performed</label>
                        <textarea name="repair_notes" id="repair_notes" class="form-control" rows="2" placeholder="e.g. Replaced display panel, applied thermal paste, tested 2 hours burn-in ok."><?= e($repair['repair_notes']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        Save Status & Notes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Replacement Parts from Inventory & Cost Calculation -->
    <div>
        
        <!-- Parts Used Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>&#128295;</span> Replacement Parts Used (<?= count($partsUsed) ?>)
                </div>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('addPartModal').style.display='flex'">
                    + Add Part from Inventory
                </button>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Part Description</th>
                            <th style="text-align:center;">Qty</th>
                            <th style="text-align:right;">Unit Price</th>
                            <th style="text-align:right;">Total</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($partsUsed)): ?>
                            <tr><td colspan="5" style="text-align:center;padding:1.5rem;color:var(--text-muted);">No replacement parts added to this job yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($partsUsed as $pt): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($pt['part_name']) ?></strong>
                                    </td>
                                    <td style="text-align:center;"><?= $pt['quantity'] ?></td>
                                    <td style="text-align:right;"><?= formatCurrency($pt['unit_price']) ?></td>
                                    <td style="text-align:right;font-weight:700;"><?= formatCurrency($pt['total']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this part and return stock to inventory?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="remove_part">
                                            <input type="hidden" name="repair_part_id" value="<?= $pt['id'] ?>">
                                            <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:1.1rem;" title="Remove part">&times;</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Labour, Charges & Totals Form -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>&#128176;</span> Billing & Labour Calculation
                </div>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="update_charges">

                    <div style="background:var(--bg-main);padding:1rem;border-radius:var(--radius-sm);border:1px solid var(--border-light);margin-bottom:1rem;">
                        <div class="summary-line">
                            <span>Total Parts Cost:</span>
                            <strong><?= formatCurrency($repair['parts_cost']) ?></strong>
                        </div>

                        <div class="summary-line" style="align-items:center;margin-top:0.5rem;">
                            <span>Labour Charges:</span>
                            <div style="display:flex;align-items:center;gap:0.3rem;">
                                <span>Rs.</span>
                                <input type="number" step="0.01" name="labour_cost" class="form-control" style="width:110px;text-align:right;padding:0.3rem 0.5rem;" value="<?= e($repair['labour_cost']) ?>" required>
                            </div>
                        </div>

                        <div class="summary-line" style="align-items:center;margin-top:0.5rem;">
                            <span>Other Charges / Courier:</span>
                            <div style="display:flex;align-items:center;gap:0.3rem;">
                                <span>Rs.</span>
                                <input type="number" step="0.01" name="other_charges" class="form-control" style="width:110px;text-align:right;padding:0.3rem 0.5rem;" value="<?= e($repair['other_charges']) ?>">
                            </div>
                        </div>

                        <div class="summary-line" style="align-items:center;margin-top:0.5rem;">
                            <span>Discount:</span>
                            <div style="display:flex;align-items:center;gap:0.3rem;">
                                <span>Rs.</span>
                                <input type="number" step="0.01" name="discount" class="form-control" style="width:110px;text-align:right;padding:0.3rem 0.5rem;color:var(--danger);" value="<?= e($repair['discount']) ?>">
                            </div>
                        </div>

                        <div class="summary-line summary-total">
                            <span>Final Total Cost:</span>
                            <span style="color:var(--blue-primary);"><?= formatCurrency($repair['final_cost']) ?></span>
                        </div>

                        <div class="summary-line" style="margin-top:0.5rem;">
                            <span>Advance Paid:</span>
                            <span style="color:var(--success);font-weight:700;"><?= formatCurrency($repair['advance_payment']) ?></span>
                        </div>

                        <div class="summary-line" style="margin-top:0.5rem;font-size:1.1rem;font-weight:800;">
                            <span>Balance Due:</span>
                            <span style="color:<?= $repair['balance'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                                <?= formatCurrency($repair['balance']) ?>
                            </span>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <button type="submit" class="btn btn-outline btn-sm">
                            Update Charges
                        </button>

                        <?php if ($repair['balance'] > 0): ?>
                            <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('settleModal').style.display='flex'">
                                &#128179; Collect Balance Payment
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Add Part Used from Inventory -->
<div id="addPartModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border-radius:12px;width:100%;max-width:500px;padding:1.5rem;box-shadow:0 20px 25px -5px rgba(0,0,0,0.3);border:1px solid var(--border-light);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;border-bottom:1px solid var(--border-light);padding-bottom:0.5rem;">
            <h3 style="font-size:1.1rem;color:var(--text-primary);">Add Part from Inventory</h3>
            <button type="button" onclick="document.getElementById('addPartModal').style.display='none'" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-primary);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="add_part">

            <div class="form-group">
                <label class="form-label" for="modal_product_id">Select Part from Inventory</label>
                <select id="modal_product_id" name="product_id" class="form-control" onchange="onPartSelected(this)">
                    <option value="">-- Choose In-Stock Part --</option>
                    <?php foreach ($inventoryProducts as $ip): ?>
                        <option value="<?= $ip['product_id'] ?>" data-price="<?= $ip['selling_price'] ?>" data-name="<?= e($ip['product_name']) ?>">
                            <?= e($ip['product_name']) ?> (Rs. <?= number_format($ip['selling_price'], 2) ?> | Stock: <?= $ip['stock_quantity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">Deducts from stock automatically and tracks repair movement.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="custom_part_name">Or Enter Custom Part Name</label>
                <input type="text" id="custom_part_name" name="custom_part_name" class="form-control" placeholder="e.g. Solder rework / Chipset IC">
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="part_qty">Quantity <span class="required">*</span></label>
                        <input type="number" id="part_qty" name="quantity" class="form-control" value="1" min="1" required>
                    </div>
                </div>

                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="part_price">Unit Price (Rs.) <span class="required">*</span></label>
                        <input type="number" step="0.01" id="part_price" name="unit_price" class="form-control" placeholder="0.00" required>
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addPartModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Add to Job</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Settle Balance Payment -->
<div id="settleModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border-radius:12px;width:100%;max-width:440px;padding:1.5rem;box-shadow:0 20px 25px -5px rgba(0,0,0,0.3);border:1px solid var(--border-light);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;border-bottom:1px solid var(--border-light);padding-bottom:0.5rem;">
            <h3 style="font-size:1.1rem;color:var(--text-primary);">Collect Repair Balance</h3>
            <button type="button" onclick="document.getElementById('settleModal').style.display='none'" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-primary);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="record_payment">

            <div class="form-group">
                <label class="form-label">Balance Due</label>
                <div style="font-size:1.3rem;font-weight:800;color:var(--danger);"><?= formatCurrency($repair['balance']) ?></div>
            </div>

            <div class="form-group">
                <label class="form-label">Payment Amount (Rs.) <span class="required">*</span></label>
                <input type="number" step="0.01" name="amount" class="form-control" max="<?= $repair['balance'] ?>" value="<?= $repair['balance'] ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-control">
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-control" value="Final handover payment">
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('settleModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-success">Record Payment & Settle</button>
            </div>
        </form>
    </div>
</div>

<script>
function onPartSelected(select) {
    var opt = select.options[select.selectedIndex];
    var price = opt.getAttribute('data-price');
    var name = opt.getAttribute('data-name');
    if (price) {
        document.getElementById('part_price').value = price;
    }
    if (name) {
        document.getElementById('custom_part_name').value = name;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
