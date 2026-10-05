<?php
/**
 * MUKIL TECH - Main Entry Redirector
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . baseUrl('admin/dashboard.php'));
} else {
    header('Location: ' . baseUrl('home.php'));
}
exit;
