<?php
/**
 * MUKIL TECH - Professional Invoice & 80mm Thermal Receipt Viewer
 */

$pageTitle = 'Sales Invoice';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'cashier']);

$db = getDB();
$invoiceNo = trim($_GET['no'] ?? '');
$format = $_GET['format'] ?? 'a4'; // 'a4' or 'thermal'

if (empty($invoiceNo)) {
    flashMessage("No invoice specified.", 'danger');
    header("Location: " . baseUrl('sales/index.php'));
    exit;
}

// Fetch Sale & Customer details
$stmt = $db->prepare("SELECT s.*, c.customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address, u.full_name as cashier_name 
                      FROM sales s 
                      LEFT JOIN customers c ON s.customer_id = c.customer_id 
                      LEFT JOIN users u ON s.user_id = u.user_id 
                      WHERE s.invoice_no = ? LIMIT 1");
$stmt->execute([$invoiceNo]);
$sale = $stmt->fetch();

if (!$sale) {
    flashMessage("Invoice '{$invoiceNo}' not found.", 'danger');
    header("Location: " . baseUrl('sales/index.php'));
    exit;
}

// Fetch Sale Items
$stmt = $db->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$stmt->execute([$sale['sale_id']]);
$items = $stmt->fetchAll();

// Handle Record Payment for unpaid / partial invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    verifyCsrfToken();
    $amount = (float)($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'Cash';
    $notes  = trim($_POST['notes'] ?? '');

    if ($amount > 0 && $amount <= $sale['balance']) {
        $newPaid = $sale['paid_amount'] + $amount;
        $newBal  = max(0, $sale['grand_total'] - $newPaid);
        $newStatus = ($newBal <= 0) ? 'Completed' : 'Pending';

        // Update sales
        $upSale = $db->prepare("UPDATE sales SET paid_amount = ?, balance = ?, status = ? WHERE sale_id = ?");
        $upSale->execute([$newPaid, $newBal, $newStatus, $sale['sale_id']]);

        // Update invoices
        $invStatus = ($newBal <= 0) ? 'Paid' : 'Partial';
        $upInv = $db->prepare("UPDATE invoices SET paid = ?, balance = ?, status = ? WHERE invoice_no = ?");
        $upInv->execute([$newPaid, $newBal, $invStatus, $invoiceNo]);

        // Insert payment audit log
        $insPay = $db->prepare("INSERT INTO payments (invoice_no, sale_id, customer_id, amount, payment_method, payment_date, received_by, notes) 
                                VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?)");
        $insPay->execute([$invoiceNo, $sale['sale_id'], $sale['customer_id'], $amount, $method, $currentUser['full_name'], $notes]);

        flashMessage("Payment of " . formatCurrency($amount) . " recorded successfully.", 'success');
        header("Location: " . baseUrl("sales/invoice.php?no=" . urlencode($invoiceNo)));
        exit;
    } else {
        flashMessage("Invalid payment amount.", 'danger');
    }
}

// Settings
$businessName = getSetting('business_name', 'MUKIL TECH');
$businessType = getSetting('business_type', 'Computer Sales • PC/Laptop Repair • Accessories');
$address      = getSetting('address', 'Vivekanandanagar, Kilinochchi, Sri Lanka');
$phone        = getSetting('phone', '077 479 5253 / 075 531 6840');
$email        = getSetting('email', 'mukiltech7@gmail.com');
$footerText   = getSetting('footer_text', 'Thank you for choosing MUKIL TECH! We appreciate your business. Genuine parts & warranty guaranteed.');
?>

<!-- Actions Bar (Hidden on Print) -->
<div class="actions-bar no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;background:var(--bg-card);padding:0.85rem 1.25rem;border-radius:var(--radius-md);border:1px solid var(--border-light);box-shadow:var(--shadow-sm);flex-wrap:wrap;gap:0.75rem;">
    <div style="display:flex;gap:0.5rem;align-items:center;">
        <a href="<?= baseUrl('sales/index.php') ?>" class="btn btn-outline btn-sm">&larr; Back to Sales</a>
        <a href="<?= baseUrl('sales/pos.php') ?>" class="btn btn-outline btn-sm">+ New Sale</a>
    </div>

    <div style="display:flex;gap:0.5rem;align-items:center;">
        <!-- Switch format -->
        <a href="<?= baseUrl('sales/invoice.php?no=' . urlencode($invoiceNo) . '&format=a4') ?>" class="btn <?= $format === 'a4' ? 'btn-navy' : 'btn-outline' ?> btn-sm">
            Standard A4
        </a>
        <a href="<?= baseUrl('sales/invoice.php?no=' . urlencode($invoiceNo) . '&format=thermal') ?>" class="btn <?= $format === 'thermal' ? 'btn-navy' : 'btn-outline' ?> btn-sm">
            80mm Thermal
        </a>

        <?php if ($sale['balance'] > 0): ?>
            <button type="button" class="btn btn-warning btn-sm" onclick="document.getElementById('paymentModal').style.display='flex'">
                + Collect Payment
            </button>
        <?php endif; ?>

        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <span>&#128438;</span> Print <?= $format === 'thermal' ? 'Receipt' : 'Invoice' ?>
        </button>
    </div>
</div>

<?php if ($format === 'thermal'): ?>
    <!-- ================= 80mm Thermal Receipt Layout ================= -->
    <div class="receipt-container">
        <div class="receipt-header">
                <div style="text-align:center;margin-bottom:6px;">
                <img src="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>" style="width:45px;height:45px;display:inline-block;" alt="Logo">
            </div>
            <h2><?= e($businessName) ?></h2>
            <div style="font-size:10px;"><?= e($businessType) ?></div>
            <div style="font-size:10px;"><?= e($address) ?></div>
            <div style="font-size:10px;">Tel: <?= e($phone) ?></div>
        </div>

        <div style="font-size:11px;margin-bottom:6px;">
            <div><strong>Receipt No:</strong> <?= e($sale['invoice_no']) ?></div>
            <div><strong>Date:</strong> <?= formatDate($sale['sale_date'], true) ?></div>
            <div><strong>Cashier:</strong> <?= e($sale['cashier_name'] ?? 'Staff') ?></div>
            <div><strong>Customer:</strong> <?= e($sale['customer_name'] ?? 'Walk-in Customer') ?></div>
            <?php if (!empty($sale['customer_phone'])): ?>
                <div><strong>Phone:</strong> <?= e($sale['customer_phone']) ?></div>
            <?php endif; ?>
        </div>

        <div class="receipt-divider"></div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Price</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td colspan="4" style="font-weight:bold;padding-top:4px;"><?= e($item['product_name']) ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align:center;"><?= $item['quantity'] ?></td>
                        <td style="text-align:right;"><?= number_format($item['unit_price'], 2) ?></td>
                        <td style="text-align:right;font-weight:bold;"><?= number_format($item['total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="receipt-divider"></div>

        <div style="font-size:11px;">
            <div style="display:flex;justify-content:space-between;">
                <span>Subtotal:</span>
                <span>Rs. <?= number_format($sale['subtotal'], 2) ?></span>
            </div>
            <?php if ($sale['discount'] > 0): ?>
                <div style="display:flex;justify-content:space-between;">
                    <span>Discount:</span>
                    <span>- Rs. <?= number_format($sale['discount'], 2) ?></span>
                </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:13px;margin:4px 0;">
                <span>GRAND TOTAL:</span>
                <span>Rs. <?= number_format($sale['grand_total'], 2) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span>Paid (<?= e($sale['payment_method']) ?>):</span>
                <span>Rs. <?= number_format($sale['paid_amount'], 2) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-weight:bold;">
                <span>Balance:</span>
                <span>Rs. <?= number_format($sale['balance'], 2) ?></span>
            </div>
        </div>

        <div class="receipt-divider"></div>

        <div style="text-align:center;font-size:10px;margin-top:6px;">
            <p><?= e($footerText) ?></p>
            <p style="margin-top:4px;">Software powered by MUKIL TECH POS</p>
        </div>
    </div>

<?php else: ?>
    <!-- ================= Standard A4 Printable Invoice Layout ================= -->
    <div class="invoice-container">
        <!-- Top Company & Invoice Header -->
        <div class="invoice-header">
            <div class="invoice-brand">
                <img src="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>" alt="MUKIL TECH Logo" class="invoice-logo">
                <div class="invoice-company-details">
                    <h2><?= e($businessName) ?></h2>
                    <div class="tagline"><?= e($businessType) ?></div>
                    <p><?= e($address) ?></p>
                    <p>Phone: <strong><?= e($phone) ?></strong> | Email: <?= e($email) ?></p>
                </div>
            </div>

            <div class="invoice-meta">
                <h3>INVOICE</h3>
                <p><strong>Invoice No:</strong> <span style="color:var(--navy-main);font-weight:800;font-size:1rem;"><?= e($sale['invoice_no']) ?></span></p>
                <p><strong>Date:</strong> <?= formatDate($sale['sale_date']) ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge <?= $sale['balance'] <= 0 ? 'badge-success' : 'badge-danger' ?>">
                        <?= $sale['balance'] <= 0 ? 'PAID' : 'BALANCE DUE' ?>
                    </span>
                </p>
            </div>
        </div>

        <!-- Billing & Customer Information -->
        <div class="invoice-addresses">
            <div class="invoice-bill-to">
                <h4>Bill To / Customer</h4>
                <div class="customer-name"><?= e($sale['customer_name'] ?? 'Walk-in Customer') ?></div>
                <p style="margin-top:3px;">&#128222; <?= e($sale['customer_phone'] ?: 'N/A') ?></p>
                <p>&#128205; <?= e($sale['customer_address'] ?: 'Kilinochchi, Sri Lanka') ?></p>
                <?php if (!empty($sale['customer_email'])): ?>
                    <p>&#9993; <?= e($sale['customer_email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="invoice-bill-to" style="border:1px dashed var(--border-medium);">
                <h4>Payment &amp; Cashier Info</h4>
                <p><strong>Payment Mode:</strong> <?= e($sale['payment_method']) ?></p>
                <p><strong>Billed By:</strong> <?= e($sale['cashier_name'] ?? 'Authorized Staff') ?></p>
                <p><strong>Notes:</strong> <?= e($sale['notes'] ?: 'Counter retail sale') ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table" style="margin-top:1rem;border:1px solid var(--border-light);">
            <thead>
                <tr style="background:var(--navy-card);color:#ffffff;">
                    <th style="background:var(--navy-card);color:#ffffff;width:50px;">#</th>
                    <th style="background:var(--navy-card);color:#ffffff;">Item Description</th>
                    <th style="background:var(--navy-card);color:#ffffff;text-align:center;width:80px;">Qty</th>
                    <th style="background:var(--navy-card);color:#ffffff;text-align:right;width:120px;">Unit Price</th>
                    <th style="background:var(--navy-card);color:#ffffff;text-align:right;width:100px;">Discount</th>
                    <th style="background:var(--navy-card);color:#ffffff;text-align:right;width:130px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($items as $item): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <strong style="color:var(--navy-main);"><?= e($item['product_name']) ?></strong>
                        </td>
                        <td style="text-align:center;"><?= $item['quantity'] ?></td>
                        <td style="text-align:right;"><?= formatCurrency($item['unit_price']) ?></td>
                        <td style="text-align:right;color:var(--text-muted);"><?= $item['discount'] > 0 ? formatCurrency($item['discount']) : '-' ?></td>
                        <td style="text-align:right;font-weight:700;color:var(--navy-main);"><?= formatCurrency($item['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals Summary Table -->
        <div class="invoice-summary-box">
            <table class="invoice-totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td style="text-align:right;"><?= formatCurrency($sale['subtotal']) ?></td>
                </tr>
                <?php if ($sale['discount'] > 0): ?>
                    <tr>
                        <td>Discount:</td>
                        <td style="text-align:right;color:var(--danger);">- <?= formatCurrency($sale['discount']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td>Grand Total:</td>
                    <td style="text-align:right;color:var(--blue-primary);"><?= formatCurrency($sale['grand_total']) ?></td>
                </tr>
                <tr>
                    <td>Paid Amount:</td>
                    <td style="text-align:right;font-weight:600;"><?= formatCurrency($sale['paid_amount']) ?></td>
                </tr>
                <tr>
                    <td>Balance Outstanding:</td>
                    <td style="text-align:right;font-weight:800;color:<?= $sale['balance'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                        <?= formatCurrency($sale['balance']) ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Signatures Section -->
        <div class="invoice-signatures">
            <div class="signature-block">
                Customer Signature
            </div>
            <div class="signature-block">
                Authorized Signature & Seal<br>
                <span style="font-size:0.7rem;font-weight:normal;color:var(--text-muted);">MUKIL TECH</span>
            </div>
        </div>

        <!-- Invoice Terms & Footer -->
        <div class="invoice-footer">
            <p><strong>Notice:</strong> 6 Months to 1 Year Warranty applicable on eligible genuine parts as per manufacturer terms. Goods sold with warranty seal.</p>
            <p style="margin-top:4px;"><?= e($footerText) ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Payment Modal if unpaid -->
<?php if ($sale['balance'] > 0): ?>
    <div id="paymentModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;">
        <div style="background:var(--bg-card);border-radius:12px;width:100%;max-width:440px;padding:1.5rem;box-shadow:0 20px 25px -5px rgba(0,0,0,0.3);border:1px solid var(--border-light);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;border-bottom:1px solid var(--border-light);padding-bottom:0.5rem;">
                <h3 style="font-size:1.1rem;color:var(--text-primary);">Record Outstanding Payment</h3>
                <button type="button" onclick="document.getElementById('paymentModal').style.display='none'" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-primary);">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="record_payment">

                <div class="form-group">
                    <label class="form-label">Balance Due</label>
                    <div style="font-size:1.3rem;font-weight:800;color:var(--danger);"><?= formatCurrency($sale['balance']) ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Amount (Rs.) <span class="required">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control" max="<?= $sale['balance'] ?>" value="<?= $sale['balance'] ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Mode</label>
                    <select name="payment_method" class="form-control">
                        <option value="Cash">Cash</option>
                        <option value="Card">Credit/Debit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Counter cash receipt">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('paymentModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
