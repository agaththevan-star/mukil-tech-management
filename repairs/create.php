<?php
/**
 * MUKIL TECH - Intake New PC / Laptop Repair Job
 */

$pageTitle = 'Intake Repair Job';

// Load auth and helpers early so we can process POST data and redirect before any output
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'technician']);

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $customerId   = (int)($_POST['customer_id'] ?? 0);
    $deviceType   = trim($_POST['device_type'] ?? 'Laptop');
    $brand        = trim($_POST['brand'] ?? '');
    $model        = trim($_POST['model'] ?? '');
    $serialNumber = trim($_POST['serial_number'] ?? '');
    $problem      = trim($_POST['problem_description'] ?? '');
    $diagnosis    = trim($_POST['diagnosis'] ?? '');
    $technician   = trim($_POST['technician'] ?? $currentUser['full_name']);
    $expectedDate = !empty($_POST['expected_date']) ? $_POST['expected_date'] : null;
    $estCost      = (float)($_POST['estimated_cost'] ?? 0);
    $advance      = (float)($_POST['advance_payment'] ?? 0);

    if ($customerId <= 0 || empty($brand) || empty($problem)) {
        $error = 'Customer, Brand, and Problem Description are required.';
    } else {
        try {
            $db->beginTransaction();

            $repairCode = generateRepairCode();
            $balance = max(0, $estCost - $advance);

            $stmt = $db->prepare("INSERT INTO repairs (repair_code, customer_id, user_id, device_type, brand, model, serial_number, problem_description, diagnosis, technician, received_date, expected_date, estimated_cost, final_cost, advance_payment, balance, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, 'Received')");
            $stmt->execute([
                $repairCode,
                $customerId,
                $currentUser['id'],
                $deviceType,
                $brand,
                $model,
                $serialNumber,
                $problem,
                $diagnosis,
                $technician,
                $expectedDate,
                $estCost,
                $estCost,
                $advance,
                $balance
            ]);
            $repairId = $db->lastInsertId();

            // If advance payment collected, log payment
            if ($advance > 0) {
                $payStmt = $db->prepare("INSERT INTO payments (invoice_no, repair_id, customer_id, amount, payment_method, payment_date, received_by, notes) 
                                         VALUES (?, ?, ?, ?, 'Cash', CURDATE(), ?, ?)");
                $payStmt->execute([
                    $repairCode,
                    $repairId,
                    $customerId,
                    $advance,
                    $currentUser['full_name'],
                    "Repair job advance intake payment"
                ]);
            }

            $db->commit();

            flashMessage("Repair ticket #{$repairCode} created successfully.", 'success');
            header("Location: " . baseUrl("repairs/view.php?id=$repairId"));
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Customers for select
$customers = $db->query("SELECT * FROM customers ORDER BY customer_name ASC")->fetchAll();
$selectedCust = (int)($_GET['customer_id'] ?? 0);

// Now include the page header (outputs HTML). All redirects must happen before this.
require_once __DIR__ . '/../includes/header.php';

?>

<div style="max-width:850px;margin:0 auto;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>&#128295;</span> PC / Laptop Intake & Token Creation
            </div>
            <a href="<?= baseUrl('repairs/index.php') ?>" class="btn btn-outline btn-sm">&larr; Back to Repairs</a>
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

                <!-- Customer selection -->
                <div class="form-group">
                    <label class="form-label" for="customer_id">Select Customer <span class="required">*</span></label>
                    <div style="display:flex;gap:0.5rem;align-items:center;">
                        <select id="customer_id" name="customer_id" class="form-control" required>
                            <option value="">-- Choose Customer --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['customer_id'] ?>" <?= ($selectedCust === (int)$c['customer_id'] || (isset($_POST['customer_id']) && $_POST['customer_id'] == $c['customer_id'])) ? 'selected' : '' ?>>
                                    <?= e($c['customer_name']) ?> (<?= e($c['phone']) ?>) - <?= e($c['address'] ?: 'Kilinochchi') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="<?= baseUrl('customers/add.php?redirect=repairs/create.php') ?>" class="btn btn-outline btn-sm" style="white-space:nowrap;">
                            + New Customer
                        </a>
                    </div>
                </div>

                <!-- Device Information -->
                <div style="background:#f8fafc;padding:1rem;border-radius:var(--radius-sm);margin-bottom:1.25rem;border:1px solid var(--border-light);">
                    <div style="font-weight:700;color:var(--navy-main);margin-bottom:0.75rem;font-size:0.9rem;">
                        &#128187; Device Specifications
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="device_type">Device Type <span class="required">*</span></label>
                                <select id="device_type" name="device_type" class="form-control">
                                    <option value="Laptop" selected>Laptop</option>
                                    <option value="Desktop PC">Desktop PC</option>
                                    <option value="All-in-One PC">All-in-One PC</option>
                                    <option value="Monitor">Monitor / Display</option>
                                    <option value="Printer">Printer / Scanner</option>
                                    <option value="Motherboard/Board">Motherboard / Component</option>
                                    <option value="Other">Other Hardware</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="brand">Brand / Manufacturer <span class="required">*</span></label>
                                <input type="text" id="brand" name="brand" class="form-control" placeholder="e.g. Dell, HP, Asus, Acer, Lenovo, Custom" required value="<?= e($_POST['brand'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="model">Model Number / Spec</label>
                                <input type="text" id="model" name="model" class="form-control" placeholder="e.g. Inspiron 15 3000 / Pavilion Gaming" value="<?= e($_POST['model'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="serial_number">Serial Number / Service Tag</label>
                                <input type="text" id="serial_number" name="serial_number" class="form-control" placeholder="e.g. 5CD1234XYZ" value="<?= e($_POST['serial_number'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Problem & Diagnosis -->
                <div class="form-group">
                    <label class="form-label" for="problem_description">Reported Problem / Symptoms <span class="required">*</span></label>
                    <textarea id="problem_description" name="problem_description" class="form-control" rows="3" placeholder="Detailed symptoms described by customer: e.g. No power, overheating, blue screen, broken hinge, liquid spill, slow performance..." required><?= e($_POST['problem_description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="diagnosis">Initial Intake Diagnosis / Notes</label>
                    <textarea id="diagnosis" name="diagnosis" class="form-control" rows="2" placeholder="e.g. Adapter working; no LED light on motherboard DC jack. Needs voltage rail inspection."><?= e($_POST['diagnosis'] ?? '') ?></textarea>
                </div>

                <!-- Estimates & Assignment -->
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="technician">Assigned Technician</label>
                            <input type="text" id="technician" name="technician" class="form-control" value="<?= e($_POST['technician'] ?? $currentUser['full_name']) ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="expected_date">Expected Completion Date</label>
                            <input type="date" id="expected_date" name="expected_date" class="form-control" value="<?= e($_POST['expected_date'] ?? date('Y-m-d', strtotime('+2 days'))) ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="estimated_cost">Estimated Total Cost (Rs.)</label>
                            <input type="number" step="0.01" id="estimated_cost" name="estimated_cost" class="form-control" placeholder="0.00" value="<?= e($_POST['estimated_cost'] ?? '0.00') ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="advance_payment">Advance Received (Rs.)</label>
                            <input type="number" step="0.01" id="advance_payment" name="advance_payment" class="form-control" placeholder="0.00" value="<?= e($_POST['advance_payment'] ?? '0.00') ?>">
                        </div>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;">
                    <a href="<?= baseUrl('repairs/index.php') ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <span>&#128295;</span> Generate Repair Intake Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
