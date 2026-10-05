<?php
/**
 * MUKIL TECH - Customers Management
 */

$pageTitle = 'Customer Directory';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$search = trim($_GET['search'] ?? '');

// Handle customer deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete' && hasRole(['admin'])) {
    verifyCsrfToken();
    $delId = (int)$_POST['customer_id'];
    try {
        $stmt = $db->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->execute([$delId]);
        flashMessage("Customer #$delId removed successfully.", 'success');
    } catch (Exception $e) {
        flashMessage("Cannot delete customer with active sales or repair records.", 'danger');
    }
    header("Location: " . baseUrl('customers/index.php'));
    exit;
}

// Fetch customers with search
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM sales s WHERE s.customer_id = c.customer_id) as total_sales,
          (SELECT COUNT(*) FROM repairs r WHERE r.customer_id = c.customer_id) as total_repairs
          FROM customers c ";
$params = [];

if (!empty($search)) {
    $query .= "WHERE c.customer_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR c.address LIKE ? ";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$query .= "ORDER BY c.customer_id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:1rem;flex:1;flex-wrap:wrap;">
            <div class="card-title">
                <span>&#128101;</span> Customer Directory (<?= count($customers) ?>)
            </div>
            
            <form method="GET" action="<?= baseUrl('customers/index.php') ?>" class="search-box">
                <span class="search-icon">&#128269;</span>
                <input type="text" name="search" class="form-control" placeholder="Search by name, phone, address..." value="<?= e($search) ?>">
            </form>
            <?php if (!empty($search)): ?>
                <a href="<?= baseUrl('customers/index.php') ?>" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </div>

        <a href="<?= baseUrl('customers/add.php') ?>" class="btn btn-primary">
            <span>+</span> Add New Customer
        </a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Purchases</th>
                    <th>Repairs</th>
                    <th>Registered</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                            No customers found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td>#<?= str_pad((string)$c['customer_id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <a href="<?= baseUrl('customers/view.php?id=' . $c['customer_id']) ?>" style="font-weight:700;color:var(--navy-main);">
                                    <?= e($c['customer_name']) ?>
                                </a>
                            </td>
                            <td>
                                <strong><?= e($c['phone']) ?></strong>
                            </td>
                            <td><?= e($c['email'] ?: '-') ?></td>
                            <td style="max-width:200px;"><?= e($c['address'] ?: '-') ?></td>
                            <td>
                                <span class="badge badge-primary"><?= $c['total_sales'] ?> Orders</span>
                            </td>
                            <td>
                                <span class="badge badge-navy"><?= $c['total_repairs'] ?> Jobs</span>
                            </td>
                            <td style="font-size:0.75rem;color:var(--text-muted);"><?= formatDate($c['created_at']) ?></td>
                            <td style="text-align:right;">
                                <div class="btn-group">
                                    <a href="<?= baseUrl('customers/view.php?id=' . $c['customer_id']) ?>" class="btn btn-outline btn-sm" title="View Profile & History">
                                        View
                                    </a>
                                    <a href="<?= baseUrl('customers/edit.php?id=' . $c['customer_id']) ?>" class="btn btn-outline btn-sm" title="Edit Customer">
                                        Edit
                                    </a>
                                    <?php if (hasRole(['admin'])): ?>
                                        <form method="POST" action="<?= baseUrl('customers/index.php') ?>" style="display:inline;" onsubmit="return confirm('Delete customer <?= e($c['customer_name']) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="customer_id" value="<?= $c['customer_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                &#128465;
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
