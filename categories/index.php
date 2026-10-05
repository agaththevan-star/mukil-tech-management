<?php
/**
 * MUKIL TECH - Categories Management
 */

$pageTitle = 'Category Management';

// Load auth and helpers early so POST handling can redirect before any output
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$db = getDB();
$error = '';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verifyCsrfToken();
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Category name cannot be blank.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->execute([$name, $desc]);
            flashMessage("Category '{$name}' created successfully.", 'success');
            header("Location: " . baseUrl('categories/index.php'));
            exit;
        } catch (Exception $e) {
            $error = 'Error: Category name might already exist.';
        }
    }
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    verifyCsrfToken();
    $catId = (int)$_POST['category_id'];
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Category name cannot be blank.';
    } else {
        try {
            $stmt = $db->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
            $stmt->execute([$name, $desc, $catId]);
            flashMessage("Category updated successfully.", 'success');
            header("Location: " . baseUrl('categories/index.php'));
            exit;
        } catch (Exception $e) {
            $error = 'Error: Could not update category.';
        }
    }
}

// Handle Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verifyCsrfToken();
    $catId = (int)$_POST['category_id'];
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$catId]);
        flashMessage("Category deleted successfully.", 'success');
        header("Location: " . baseUrl('categories/index.php'));
        exit;
    } catch (Exception $e) {
        flashMessage("Cannot delete category with associated products.", 'danger');
    }
}

// Fetch all categories with product counts
// Fetch categories
$categories = $db->query("SELECT c.*, COUNT(p.product_id) as total_products 
                          FROM categories c 
                          LEFT JOIN products p ON c.category_id = p.category_id 
                          GROUP BY c.category_id 
                          ORDER BY c.category_name ASC")->fetchAll();

// Include header after POST handling so header() redirects work
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <span class="alert-icon">&#9888;</span>
        <span class="alert-content"><?= e($error) ?></span>
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:360px 1fr;gap:1.5rem;align-items:start;">
    <!-- Add Category Form Panel -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>+</span> Add New Category
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label" for="category_name">Category Name <span class="required">*</span></label>
                    <input type="text" id="category_name" name="category_name" class="form-control" placeholder="e.g. Gaming Peripherals" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Brief description of items in this category"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    Save Category
                </button>
            </form>
        </div>
    </div>

    <!-- Category List Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>&#128194;</span> Existing Categories (<?= count($categories) ?>)
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Total Products</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>#<?= $cat['category_id'] ?></td>
                            <td>
                                <strong><?= e($cat['category_name']) ?></strong>
                            </td>
                            <td style="color:var(--text-secondary);font-size:0.82rem;max-width:260px;">
                                <?= e($cat['description'] ?: '-') ?>
                            </td>
                            <td>
                                <a href="<?= baseUrl('products/index.php?category_id=' . $cat['category_id']) ?>" class="badge badge-primary">
                                    <?= $cat['total_products'] ?> Items
                                </a>
                            </td>
                            <td style="text-align:right;">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="editCat(<?= $cat['category_id'] ?>, '<?= addslashes(e($cat['category_name'])) ?>', '<?= addslashes(e($cat['description'] ?? '')) ?>')">
                                        Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete category <?= e($cat['category_name']) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" <?= $cat['total_products'] > 0 ? 'disabled title="Cannot delete category with products"' : '' ?>>
                                            &#128465;
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Simple Vanilla Edit Modal -->
<div id="editModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-card);border-radius:12px;width:100%;max-width:440px;padding:1.5rem;box-shadow:0 20px 25px -5px rgba(0,0,0,0.3);border:1px solid var(--border-light);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;border-bottom:1px solid var(--border-light);padding-bottom:0.5rem;">
            <h3 style="font-size:1.1rem;color:var(--text-primary);">Edit Category</h3>
            <button type="button" onclick="document.getElementById('editModal').style.display='none'" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-primary);">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="modal_cat_id" name="category_id">

            <div class="form-group">
                <label class="form-label">Category Name <span class="required">*</span></label>
                <input type="text" id="modal_cat_name" name="category_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea id="modal_cat_desc" name="description" class="form-control" rows="3"></textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCat(id, name, desc) {
    document.getElementById('modal_cat_id').value = id;
    document.getElementById('modal_cat_name').value = name;
    document.getElementById('modal_cat_desc').value = desc;
    document.getElementById('editModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
