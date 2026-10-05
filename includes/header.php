<?php
/**
 * MUKIL TECH - Main Header Template
 */


// Start output buffering to avoid "headers already sent" issues when
// pages call header() after including the header (which outputs the sidebar).
// Buffering is flushed in footer.php.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
if (function_exists('ob_start') && !headers_sent()) {
    @ob_start();
}

// Enforce login on all pages including header
requireLogin();

$currentUser = currentUser();
$businessName = getSetting('business_name', 'MUKIL TECH');
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e($businessName) ?></title>
    <link rel="icon" type="image/png" href="<?= baseUrl('assets/images/mukil-tech-logo.png') ?>">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
    <script>
        // Apply theme immediately to prevent flash
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-theme');
        }
    </script>
</head>
<body>
<div class="app-container">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="sidebar-overlay" onclick="document.querySelector('.sidebar').classList.remove('mobile-open'); this.classList.remove('active');"></div>

    <div class="main-wrapper">
        <header class="top-header">
            <div class="header-left">
                <button type="button" class="btn btn-outline btn-sm no-print mobile-toggle-btn" onclick="document.querySelector('.sidebar').classList.toggle('mobile-open'); document.querySelector('.sidebar-overlay').classList.toggle('active');">
                    &#9776;
                </button>
                <div class="page-headline">
                    <?= e($pageTitle) ?>
                </div>
            </div>

            <div class="header-right no-print">
                <?php if (hasRole(['admin', 'cashier'])): ?>
                    <a href="<?= baseUrl('sales/pos.php') ?>" class="btn btn-primary btn-sm">
                        <span>&#128722;</span> POS Billing
                    </a>
                <?php endif; ?>

                <?php if (hasRole(['admin', 'technician'])): ?>
                    <a href="<?= baseUrl('repairs/create.php') ?>" class="btn btn-navy btn-sm">
                        <span>&#128295;</span> New Repair
                    </a>
                <?php endif; ?>

                <button type="button" class="btn btn-outline btn-sm no-print" onclick="toggleTheme()" id="theme-toggle" title="Toggle Theme" style="border:none;background:transparent;font-size:1.2rem;padding:0.25rem 0.5rem;cursor:pointer;">
                    <span id="theme-icon">&#127769;</span>
                </button>

                <div class="header-badge" style="display:flex;align-items:center;gap:6px;padding:0.35rem 0.65rem;">
                    <span>&#128100;</span>
                    <label for="roleSwitchSelect" style="font-size:0.75rem;font-weight:600;color:var(--text-secondary);margin:0;">Active Role:</label>
                    <select id="roleSwitchSelect" onchange="const u = new URL(window.location.href); u.searchParams.set('switch_role', this.value); window.location.href = u.href;" style="background:var(--bg-card);border:1px solid var(--border-medium);border-radius:4px;padding:2px 6px;font-size:0.75rem;font-weight:700;color:var(--text-primary);cursor:pointer;">
                        <option value="admin" <?= ($currentUser['role'] === 'admin') ? 'selected' : '' ?>>Admin (Full Access)</option>
                        <option value="cashier" <?= ($currentUser['role'] === 'cashier') ? 'selected' : '' ?>>Cashier (POS & Invoices)</option>
                        <option value="technician" <?= ($currentUser['role'] === 'technician') ? 'selected' : '' ?>>Technician (Repairs)</option>
                    </select>
                </div>
            </div>
        </header>

        <main class="content-body">
            <?= renderFlash() ?>
