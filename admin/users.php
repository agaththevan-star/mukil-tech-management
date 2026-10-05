<?php
/**
 * MUKIL TECH - Staff & Roles Management
 */

$pageTitle = 'Staff & Roles Management';
require_once __DIR__ . '/../includes/header.php';

requireRole(['admin']);

$db = getDB();

// Handle user deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    verifyCsrfToken();
    $delId = (int)$_POST['user_id'];
    
    // Prevent deleting the main admin or oneself
    $currentUser = currentUser();
    if ($delId === $currentUser['user_id']) {
        flashMessage("You cannot delete your own account.", 'danger');
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$delId]);
            flashMessage("Staff member removed successfully.", 'success');
        } catch (Exception $e) {
            flashMessage("Cannot delete this user. They may be linked to existing records.", 'danger');
        }
    }
    header("Location: " . baseUrl('admin/users.php'));
    exit;
}

// Fetch users
$stmt = $db->query("SELECT * FROM users ORDER BY role ASC, full_name ASC");
$users = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span>&#128100;</span> Staff & Roles Management
        </div>
        <div>
            <!-- Add New User (Stub - requires add_user.php) -->
            <button class="btn btn-primary" onclick="alert('Add user functionality to be implemented')">
                <span>+</span> Add Staff Member
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                            No staff members found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= str_pad((string)$u['user_id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td style="font-weight:700;color:var(--navy-main);">
                                <?= e($u['full_name']) ?>
                            </td>
                            <td><?= e($u['username']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-navy' ?>">
                                    <?= ucfirst(e($u['role'])) ?>
                                </span>
                            </td>
                            <td><?= e($u['email'] ?: '-') ?></td>
                            <td><?= e($u['phone'] ?: '-') ?></td>
                            <td>
                                <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= ucfirst(e($u['status'])) ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div class="btn-group">
                                    <button class="btn btn-outline btn-sm" onclick="alert('Edit user functionality to be implemented')" title="Edit User">
                                        Edit
                                    </button>
                                    <form method="POST" action="<?= baseUrl('admin/users.php') ?>" style="display:inline;" onsubmit="return confirm('Delete staff member <?= e($u['full_name']) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                            &#128465;
                                        </button>
                                    </form>
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
