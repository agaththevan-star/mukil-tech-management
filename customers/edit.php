<?php
/**
 * MUKIL TECH - Edit Customer
 */

$pageTitle = 'Edit Customer';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    flashMessage("Customer not found.", 'danger');
    header("Location: " . baseUrl('customers/index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($phone)) {
        $error = 'Customer Name and Phone Number are required.';
    } else {
        try {
            $updateStmt = $db->prepare("UPDATE customers SET customer_name = ?, phone = ?, email = ?, address = ? WHERE customer_id = ?");
            $updateStmt->execute([$name, $phone, $email, $address, $id]);

            flashMessage("Customer profile updated successfully.", 'success');
            header("Location: " . baseUrl("customers/view.php?id=$id"));
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div style="max-width:700px;margin:0 auto;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>&#128100;</span> Edit Customer Profile #<?= $customer['customer_id'] ?>
            </div>
            <a href="<?= baseUrl('customers/view.php?id=' . $customer['customer_id']) ?>" class="btn btn-outline btn-sm">&larr; Back to Profile</a>
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
                    <input type="text" id="customer_name" name="customer_name" class="form-control" required value="<?= e($_POST['customer_name'] ?? $customer['customer_name']) ?>" autofocus>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number <span class="required">*</span></label>
                            <input type="text" id="phone" name="phone" class="form-control" required value="<?= e($_POST['phone'] ?? $customer['phone']) ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? $customer['email']) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Postal / Shop Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?= e($_POST['address'] ?? $customer['address']) ?></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;">
                    <a href="<?= baseUrl('customers/view.php?id=' . $customer['customer_id']) ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <span>&#128190;</span> Update Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
