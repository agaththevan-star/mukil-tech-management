<?php
/**
 * MUKIL TECH - User Management & RBAC
 */

$pageTitle = 'User Management';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$db = getDB();
$error = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verifyCsrfToken();
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'cashier';
    $status   = $_POST['status'] ?? 'Active';

    if (empty($username) || empty($password) || empty($fullname)) {
        $error = 'All fields are required to register a user.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed, $fullname, $role, $status]);

            flashMessage("User '{$username}' created successfully.", 'success');
            header("Location: " . baseUrl('users/index.php'));
            exit;
        } catch (Exception $e) {
            $error = 'Username already exists. Please choose a unique username.';
        }
    }
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    verifyCsrfToken();
    $userId   = (int)$_POST['user_id'];
    $fullname = trim($_POST['full_name'] ?? '');
    $role     = $_POST['role'] ?? 'cashier';
    $status   = $_POST['status'] ?? 'Active';
    $newPass  = $_POST['new_password'] ?? '';

    if (empty($fullname)) {
        $error = 'Full Name cannot be blank.';
    } else {
        try {
            if (!empty($newPass)) {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET full_name = ?, role = ?, status = ?, password = ? WHERE user_id = ?");
                $stmt->execute([$fullname, $role, $status, $hashed, $userId]);
            } else {
                $stmt = $db->prepare("UPDATE users SET full_name = ?, role = ?, status = ? WHERE user_id = ?");
                $stmt->execute([$fullname, $role, $status, $userId]);
            }

            flashMessage("User account updated.", 'success');
            header("Location: " . baseUrl('users/index.php'));
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verifyCsrfToken();
    $userId = (int)$_POST['user_id'];

    if ($userId === (int)$currentUser['id']) {
        flashMessage("You cannot delete your own logged-in account.", 'danger');
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            flashMessage("User account removed.", 'success');
        } catch (Exception $e) {
            flashMessage("Could not delete user with linked operational activity.", 'danger');
        }
    }
    header("Location: " . baseUrl('users/index.php'));
    exit;
}

$users = $db->query("SELECT * FROM users ORDER BY user_id ASC")->fetchAll();
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <span class="alert-icon">&#9888;</span>
        <span class="alert-content"><?= e($error) ?></span>
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:380px 1fr;gap:1.5rem;align-items:start;">
    
    <!-- Add User Panel -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>+</span> Register New User
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. John Doe" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. jdoe" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">User Role <span class="required">*</span></label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="admin">Admin (Full System Access)</option>
                        <option value="cashier" selected>Cashier (POS & Invoicing)</option>
                        <option value="technician">Technician (PC/Laptop Repairs)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Account Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="Active" selected>Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    Create User Account
                </button>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>&#128101;</span> System Users & Role Permissions (<?= count($users) ?>)
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
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['user_id'] ?></td>
                            <td>
                                <strong><?= e($u['full_name']) ?></strong>
                                <?php if ($u['user_id'] === $currentUser['id']): ?>
                                    <span class="badge badge-success" style="font-size:0.65rem;">You</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($u['username']) ?></code></td>
                            <td>
                                <?php
                                    $roleClass = 'badge-navy';
                                    if ($u['role'] === 'admin') $roleClass = 'badge-danger';
                                    if ($u['role'] === 'cashier') $roleClass = 'badge-primary';
                                    if ($u['role'] === 'technician') $roleClass = 'badge-warning';
                                ?>
                                <span class="badge <?= $roleClass ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $u['status'] === 'Active' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= e($u['status']) ?>
                                </span>
                            </td>
                            <td style="font-size:0.75rem;color:var(--text-muted);"><?= formatDate($u['created_at']) ?></td>
                            <td style="text-align:right;">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="editUser(<?= $u['user_id'] ?>, '<?= addslashes(e($u['full_name'])) ?>', '<?= e($u['role']) ?>', '<?= e($u['status']) ?>')">
                                        Edit
                                    </button>
                                    <?php if ($u['user_id'] !== $currentUser['id']): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user <?= e($u['username']) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete User">
                                                &#128465;
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border-radius:12px;width:100%;max-width:440px;padding:1.5rem;box-shadow:0 20px 25px -5px rgba(0,0,0,0.3);border:1px solid var(--border-light);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;border-bottom:1px solid var(--border-light);padding-bottom:0.5rem;">
            <h3 style="font-size:1.1rem;color:var(--text-primary);">Edit User Account</h3>
            <button type="button" onclick="document.getElementById('editUserModal').style.display='none'" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-primary);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_user_id" name="user_id">

            <div class="form-group">
                <label class="form-label" for="edit_full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="edit_full_name" name="full_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit_role">User Role</label>
                <select id="edit_role" name="role" class="form-control">
                    <option value="admin">Admin</option>
                    <option value="cashier">Cashier</option>
                    <option value="technician">Technician</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit_status">Status</label>
                <select id="edit_status" name="status" class="form-control">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit_new_password">Reset Password (Leave blank to keep current)</label>
                <input type="password" id="edit_new_password" name="new_password" class="form-control" placeholder="New password">
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('editUserModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, name, role, status) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_full_name').value = name;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_new_password').value = '';
    document.getElementById('editUserModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
