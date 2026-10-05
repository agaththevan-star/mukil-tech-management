<?php
/**
 * MUKIL TECH - Add Supplier
 */

$pageTitle = 'Add Supplier';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name = trim($_POST['supplier_name'] ?? '');
    $company = trim($_POST['company_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($company)) {
        $error = 'Supplier Name and Company Name are required.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO suppliers (supplier_name, company_name, phone, email, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $company, $phone, $email, $address]);
            $newId = $db->lastInsertId();

            flashMessage("Supplier '{$company}' added successfully.", 'success');
            header("Location: " . baseUrl("suppliers/view.php?id=$newId"));
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
                <span>&#128179;</span> New Hardware / Parts Supplier
            </div>
            <a href="<?= baseUrl('suppliers/index.php') ?>" class="btn btn-outline btn-sm">&larr; Back to Suppliers</a>
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

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="supplier_name">Contact Person <span class="required">*</span></label>
                            <input type="text" id="supplier_name" name="supplier_name" class="form-control" placeholder="e.g. K. Sivakumar" required autofocus value="<?= e($_POST['supplier_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="company_name">Company Name <span class="required">*</span></label>
                            <input type="text" id="company_name" name="company_name" class="form-control" placeholder="e.g. Lanka Tech Distributers" required value="<?= e($_POST['company_name'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone / Mobile</label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. 011 258 7410" value="<?= e($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="e.g. orders@distributor.lk" value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Address / Location</label>
                    <textarea id="address" name="address" class="form-control" rows="3" placeholder="e.g. Unity Plaza / Liberty Plaza, Colombo"><?= e($_POST['address'] ?? '') ?></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;">
                    <a href="<?= baseUrl('suppliers/index.php') ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Save Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
