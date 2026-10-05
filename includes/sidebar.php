<?php
/**
 * MUKIL TECH - Main Sidebar Navigation
 */

$currentPage = $_SERVER['SCRIPT_NAME'] ?? '';
$currentUser = currentUser();

// Helper to check active nav link
function isNavActive($path) {
    global $currentPage;
    return (strpos($currentPage, $path) !== false) ? 'active' : '';
}

// Check low stock count for badge
$lowStockCount = 0;
$pendingRepairsCount = 0;
try {
    $db = getDB();
    $lowStmt = $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= minimum_stock");
    $lowStockCount = (int)$lowStmt->fetchColumn();

    $repStmt = $db->query("SELECT COUNT(*) FROM repairs WHERE status NOT IN ('Delivered', 'Cancelled')");
    $pendingRepairsCount = (int)$repStmt->fetchColumn();
} catch (Exception $e) {
    // Ignore error in display
}
?>
<aside class="sidebar no-print">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>" alt="MUKIL TECH Logo">
        </div>
        <div class="sidebar-title">
            <h1>MUKIL TECH</h1>
            <span>PC Sales & Repairs</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Core</div>
        <a href="<?= baseUrl('admin/dashboard.php') ?>" class="nav-link <?= isNavActive('dashboard.php') ?>">
            <span class="nav-icon">&#128202;</span>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-title">Sales & Services</div>
        <?php if (hasRole(['admin', 'cashier'])): ?>
            <a href="<?= baseUrl('sales/pos.php') ?>" class="nav-link <?= isNavActive('pos.php') ?>">
                <span class="nav-icon">&#128722;</span>
                <span>POS Billing</span>
            </a>
            <a href="<?= baseUrl('sales/index.php') ?>" class="nav-link <?= (isNavActive('sales/index.php') || isNavActive('sales/invoice.php')) ?>">
                <span class="nav-icon">&#128196;</span>
                <span>Invoices & Sales</span>
            </a>
        <?php endif; ?>

        <?php if (hasRole(['admin', 'technician'])): ?>
            <a href="<?= baseUrl('repairs/index.php') ?>" class="nav-link <?= isNavActive('repairs/') ?>">
                <span class="nav-icon">&#128295;</span>
                <span>PC & Laptop Repairs</span>
                <?php if ($pendingRepairsCount > 0): ?>
                    <span class="nav-badge" style="background:#0284c7;"><?= $pendingRepairsCount ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <a href="<?= baseUrl('customers/index.php') ?>" class="nav-link <?= isNavActive('customers/') ?>">
            <span class="nav-icon">&#128101;</span>
            <span>Customers</span>
        </a>

        <div class="nav-section-title">Inventory</div>
        <a href="<?= baseUrl('products/index.php') ?>" class="nav-link <?= (isNavActive('products/index.php') || isNavActive('products/add.php') || isNavActive('products/edit.php')) ?>">
            <span class="nav-icon">&#128230;</span>
            <span>Products & Parts</span>
            <?php if ($lowStockCount > 0): ?>
                <span class="nav-badge" title="Low stock alert"><?= $lowStockCount ?></span>
            <?php endif; ?>
        </a>

        <?php if (hasRole(['admin'])): ?>
            <a href="<?= baseUrl('categories/index.php') ?>" class="nav-link <?= isNavActive('categories/') ?>">
                <span class="nav-icon">&#128194;</span>
                <span>Categories</span>
            </a>
            <a href="<?= baseUrl('products/stock.php') ?>" class="nav-link <?= isNavActive('products/stock.php') ?>">
                <span class="nav-icon">&#128259;</span>
                <span>Stock Movements</span>
            </a>
            <a href="<?= baseUrl('suppliers/index.php') ?>" class="nav-link <?= isNavActive('suppliers/') ?>">
                <span class="nav-icon">&#128179;</span>
                <span>Suppliers</span>
            </a>
        <?php endif; ?>

        <?php if (hasRole(['admin'])): ?>
            <div class="nav-section-title">Finance & Reports</div>
            <a href="<?= baseUrl('expenses/index.php') ?>" class="nav-link <?= isNavActive('expenses/') ?>">
                <span class="nav-icon">&#128184;</span>
                <span>Expenses</span>
            </a>
            <a href="<?= baseUrl('reports/index.php') ?>" class="nav-link <?= isNavActive('reports/') ?>">
                <span class="nav-icon">&#128200;</span>
                <span>Analytics & Reports</span>
            </a>

            <div class="nav-section-title">Administration</div>
            <a href="<?= baseUrl('admin/users.php') ?>" class="nav-link <?= isNavActive('users.php') ?>">
                <span class="nav-icon">&#128100;</span>
                <span>Staff & Roles</span>
            </a>
            <a href="<?= baseUrl('admin/settings.php') ?>" class="nav-link <?= isNavActive('settings.php') ?>">
                <span class="nav-icon">&#9881;</span>
                <span>System Settings</span>
            </a>
            <a href="<?= baseUrl('admin/backup.php') ?>" class="nav-link <?= isNavActive('backup.php') ?>">
                <span class="nav-icon">&#128190;</span>
                <span>Database Backup</span>
            </a>
        <?php endif; ?>

        <div class="nav-section-title" style="margin-top:0.5rem;">General</div>
        <a href="<?= baseUrl('home.php') ?>" class="nav-link" title="Go to Public Home Page">
            <span class="nav-icon">&#127968;</span>
            <span>Home Page</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">
                <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-info-text">
                <div class="user-name"><?= e($currentUser['full_name'] ?? 'Staff') ?></div>
                <div class="user-role-tag"><?= e($currentUser['role'] ?? 'User') ?></div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <a href="<?= baseUrl('home.php') ?>" style="color:#38bdf8;font-size:1.1rem;" title="Go to Home Page">&#127968;</a>
            <a href="<?= baseUrl('logout.php') ?>" style="color:#ef4444;font-size:1.1rem;" title="Logout">&#128682;</a>
        </div>
    </div>
</aside>
