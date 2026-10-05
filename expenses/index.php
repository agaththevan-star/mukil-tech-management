<?php
/**
 * MUKIL TECH - Expense Management
 */

$pageTitle = 'Operating Expenses';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$db = getDB();
$error = '';

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verifyCsrfToken();
    $cat    = $_POST['category'] ?? 'Other';
    $desc   = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $date   = !empty($_POST['expense_date']) ? $_POST['expense_date'] : date('Y-m-d');
    $method = $_POST['payment_method'] ?? 'Cash';

    if ($amount <= 0 || empty($desc)) {
        $error = 'Description and a valid amount are required.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO expenses (category, description, amount, expense_date, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$cat, $desc, $amount, $date, $method]);

            flashMessage("Expense of " . formatCurrency($amount) . " logged.", 'success');
            header("Location: " . baseUrl('expenses/index.php'));
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Delete Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verifyCsrfToken();
    $expId = (int)$_POST['expense_id'];
    $stmt = $db->prepare("DELETE FROM expenses WHERE expense_id = ?");
    $stmt->execute([$expId]);
    flashMessage("Expense deleted.", 'success');
    header("Location: " . baseUrl('expenses/index.php'));
    exit;
}

// Filters
$filterCategory = trim($_GET['category'] ?? '');
$filterMonth = trim($_GET['month'] ?? date('Y-m'));

$sql = "SELECT * FROM expenses WHERE 1=1 ";
$params = [];

if (!empty($filterCategory)) {
    $sql .= "AND category = ? ";
    $params[] = $filterCategory;
}

if (!empty($filterMonth)) {
    $sql .= "AND DATE_FORMAT(expense_date, '%Y-%m') = ? ";
    $params[] = $filterMonth;
}

$sql .= "ORDER BY expense_date DESC, expense_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Month Total & All-Time Total
$monthTotal = 0;
foreach ($expenses as $ex) {
    $monthTotal += $ex['amount'];
}

$categoriesList = ['Rent', 'Electricity', 'Internet', 'Transport', 'Parts Purchase', 'Salary', 'Maintenance', 'Other'];
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <span class="alert-icon">&#9888;</span>
        <span class="alert-content"><?= e($error) ?></span>
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:360px 1fr;gap:1.5rem;align-items:start;">
    
    <!-- Add Expense Form Panel -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>+</span> Record New Expense
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label" for="category">Expense Category <span class="required">*</span></label>
                    <select id="category" name="category" class="form-control" required>
                        <?php foreach ($categoriesList as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="amount">Amount (Rs.) <span class="required">*</span></label>
                    <input type="number" step="0.01" id="amount" name="amount" class="form-control" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="expense_date">Date of Expense</label>
                    <input type="date" id="expense_date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="payment_method">Payment Mode</label>
                    <select id="payment_method" name="payment_method" class="form-control">
                        <option value="Cash" selected>Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Card">Card</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description / Notes <span class="required">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="e.g. SLT fiber monthly connection or Shop rent" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    Save Expense Record
                </button>
            </form>
        </div>
    </div>

    <!-- Expenses Listing & Summary -->
    <div>
        <div class="card">
            <div class="card-header">
                <div style="display:flex;align-items:center;gap:1rem;flex:1;flex-wrap:wrap;">
                    <div class="card-title">
                        <span>&#128184;</span> Operating Expenses
                    </div>

                    <!-- Month selector -->
                    <form method="GET" style="display:inline-flex;align-items:center;gap:0.4rem;">
                        <input type="month" name="month" class="form-control" value="<?= e($filterMonth) ?>" onchange="this.form.submit()" style="padding:0.35rem 0.6rem;font-size:0.82rem;">
                        <?php if ($filterCategory): ?><input type="hidden" name="category" value="<?= e($filterCategory) ?>"><?php endif; ?>
                    </form>

                    <!-- Category filter -->
                    <form method="GET" style="display:inline-flex;align-items:center;gap:0.4rem;">
                        <input type="hidden" name="month" value="<?= e($filterMonth) ?>">
                        <select name="category" class="form-control" onchange="this.form.submit()" style="padding:0.35rem 0.6rem;font-size:0.82rem;">
                            <option value="">All Categories</option>
                            <?php foreach ($categoriesList as $cat): ?>
                                <option value="<?= $cat ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <?php if ($filterCategory || $filterMonth !== date('Y-m')): ?>
                        <a href="<?= baseUrl('expenses/index.php') ?>" class="btn btn-outline btn-sm">Reset</a>
                    <?php endif; ?>
                </div>

                <div style="font-weight:800;color:var(--danger);font-size:1.05rem;">
                    Total: <?= formatCurrency($monthTotal) ?>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                                    No expenses logged for <?= e($filterMonth) ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $ex): ?>
                                <tr>
                                    <td style="white-space:nowrap;font-size:0.82rem;color:var(--text-muted);">
                                        <?= formatDate($ex['expense_date']) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-navy"><?= e($ex['category']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= e($ex['description']) ?></strong>
                                    </td>
                                    <td><?= e($ex['payment_method']) ?></td>
                                    <td>
                                        <strong style="color:var(--danger);"><?= formatCurrency($ex['amount']) ?></strong>
                                    </td>
                                    <td style="text-align:right;">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this expense?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="expense_id" value="<?= $ex['expense_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                &#128465;
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
