<?php
/**
 * MUKIL TECH - Add Customer
 */

$pageTitle = 'Add New Customer';

// Load auth and helpers early so POST handling can redirect before any output
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($phone)) {
        $error = 'Customer Name and Phone Number are required fields.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO customers (customer_name, phone, email, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $email, $address]);
            $newId = $db->lastInsertId();

            flashMessage("Customer '{$name}' created successfully.", 'success');
            
            // If redirected from POS or Repair
            if (!empty($_GET['redirect'])) {
                header("Location: " . baseUrl(urldecode($_GET['redirect']) . "?customer_id=" . $newId));
            } else {
                header("Location: " . baseUrl("customers/view.php?id=" . $newId));
            }
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Include header after POST handling so header() can run without 'headers already sent'
require_once __DIR__ . '/../includes/header.php';

?>

<div style="max-width:700px;margin:0 auto;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>&#128100;</span> Customer Registration Form
            </div>
            <a href="<?= baseUrl('customers/index.php') ?>" class="btn btn-outline btn-sm">&larr; Back to Directory</a>
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
                    <label class="form-label" for="customer_name">Full Name / Company Name <span class="required">*</span></label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" placeholder="e.g. S. Agaththevan" required value="<?= e($_POST['customer_name'] ?? '') ?>" autofocus>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number <span class="required">*</span></label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. 077 479 5253" required value="<?= e($_POST['phone'] ?? '') ?>">
                            <div class="form-hint">Primary mobile or WhatsApp number for SMS/Receipts</div>
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="e.g. customer@gmail.com" value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Postal / Shop Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3" placeholder="e.g. Vivekanandanagar, Kilinochchi"><?= e($_POST['address'] ?? '') ?></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;">
                    <a href="<?= baseUrl('customers/index.php') ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <span>&#128190;</span> Save Customer Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
