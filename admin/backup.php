<?php
/**
 * MUKIL TECH - MySQL Database Backup & Restore Engine
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);
$db = getDB();

// Handle Direct SQL Download Export Action
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sqlDump = "-- ==========================================================\n";
    $sqlDump .= "-- MUKIL TECH - Complete MySQL Database Backup\n";
    $sqlDump .= "-- Vivekanandanagar, Kilinochchi, Sri Lanka\n";
    $cu = currentUser();
    $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . " by " . ($cu['username'] ?? 'system') . "\n";
    $sqlDump .= "-- Database: mukiltech\n";
    $sqlDump .= "-- ==========================================================\n\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sqlDump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
    $sqlDump .= "SET time_zone = '+00:00';\n\n";

    foreach ($tables as $table) {
        $sqlDump .= "-- --------------------------------------------------------\n";
        $sqlDump .= "-- Table structure for table `{$table}`\n";
        $sqlDump .= "-- --------------------------------------------------------\n";
        $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";

        $createStmt = $db->query("SHOW CREATE TABLE `{$table}`");
        $createRow = $createStmt->fetch(PDO::FETCH_NUM);
        $sqlDump .= $createRow[1] . ";\n\n";

        $dataStmt = $db->query("SELECT * FROM `{$table}`");
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $sqlDump .= "-- Dumping data for table `{$table}`\n";
            foreach ($rows as $row) {
                $fields = array_keys($row);
                $values = array_values($row);

                $escapedValues = array_map(function($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return $db->quote($v);
                }, $values);

                $sqlDump .= "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
            }
            $sqlDump .= "\n";
        }
    }

    $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $sqlDump .= "-- End of Backup\n";

    $filename = "mukiltech_backup_" . date('Y-m-d_His') . ".sql";
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sqlDump));
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    echo $sqlDump;
    exit;
}

// Handle CSV export (zipped CSV files for each table) - useful for Excel import
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    requireRole(['admin']);

    // collect tables
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    // helper to escape CSV values
    $escapeCsv = function($v) {
        if ($v === null) return '';
        $s = (string)$v;
        $s = str_replace('"', '""', $s);
        if (strpos($s, ',') !== false || strpos($s, '"') !== false || strpos($s, "\n") !== false) {
            return '"' . $s . '"';
        }
        return $s;
    };

    // create temporary zip
    $tmpfile = tempnam(sys_get_temp_dir(), 'mukilzip_');
    $zip = new ZipArchive();
    if ($zip->open($tmpfile, ZipArchive::CREATE) !== true) {
        http_response_code(500);
        echo 'Failed to create archive file.';
        exit;
    }

    foreach ($tables as $table) {
        try {
            $rowsStmt = $db->query("SELECT * FROM `" . str_replace('`','', $table) . "`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // skip problematic table but log
            error_log('[mukiltech][backup] Could not read table ' . $table . ': ' . $e->getMessage());
            continue;
        }

        $csv = "";
        // header
        if (!empty($rows)) {
            $cols = array_keys($rows[0]);
            $csv .= implode(',', array_map($escapeCsv, $cols)) . "\n";
            foreach ($rows as $r) {
                $vals = [];
                foreach ($cols as $c) {
                    $vals[] = $escapeCsv($r[$c] ?? '');
                }
                $csv .= implode(',', $vals) . "\n";
            }
        } else {
            // empty table: still add header (try to fetch column names)
            $colStmt = $db->query("SHOW COLUMNS FROM `" . str_replace('`','', $table) . "`");
            $cols = [];
            while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
                $cols[] = $col['Field'];
            }
            if (!empty($cols)) {
                $csv .= implode(',', array_map($escapeCsv, $cols)) . "\n";
            }
        }

        // add to zip
        $zip->addFromString($table . '.csv', "\xEF\xBB\xBF" . $csv); // add BOM for Excel UTF-8 compatibility
    }

    $zip->close();

    $filename = 'mukiltech_csv_backup_' . date('Y-m-d_His') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpfile));
    readfile($tmpfile);
    @unlink($tmpfile);
    exit;
}

$pageTitle = 'Database Backup & Restore';
require_once __DIR__ . '/../includes/header.php';

$message = '';
$error = '';

// Handle Restore Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore') {
    verifyCsrfToken();

    if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['sql_file']['tmp_name'];
        $fileName = $_FILES['sql_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'sql') {
            $error = 'Invalid file format. Only .sql files are supported for database restore.';
        } else {
            try {
                $sqlContent = file_get_contents($fileTmp);
                $db->exec($sqlContent);
                $message = "Database successfully restored from '" . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . "'.";
            } catch (Exception $e) {
                $error = logException($e, 'Restore failed due to an internal error.');
            }
        }
    } else {
        $error = 'Please select a valid .sql backup file to restore.';
    }
}

// Get DB stats
$tableStats = $db->query("SELECT table_name, table_rows, round(((data_length + index_length) / 1024), 2) as size_kb 
                          FROM information_schema.tables 
                          WHERE table_schema = 'mukiltech' 
                          ORDER BY table_name ASC")->fetchAll();

$totalRows = 0;
$totalSizeKb = 0;
$normalizedStats = [];
foreach ($tableStats as $ts) {
    $ts = array_change_key_case($ts, CASE_LOWER);
    $normalizedStats[] = $ts;
    $totalRows += (int)($ts['table_rows'] ?? 0);
    $totalSizeKb += (float)($ts['size_kb'] ?? 0);
}
$tableStats = $normalizedStats;
?>

<div style="max-width:850px;margin:0 auto;">
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <span class="alert-icon">&#10003;</span>
            <span class="alert-content"><?= e($message) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <span class="alert-icon">&#9888;</span>
            <span class="alert-content"><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Backup Notice / Status -->
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:var(--radius-md);padding:1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;gap:1rem;align-items:center;">
            <div style="width:48px;height:48px;border-radius:10px;background:var(--blue-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                &#128190;
            </div>
            <div>
                <h3 style="font-size:1.1rem;color:var(--navy-main);font-weight:700;">Database Health & Protection</h3>
                <div style="font-size:0.85rem;color:var(--text-secondary);">
                    Database: <strong>mukiltech</strong> &bull; Total Tables: <strong><?= count($tableStats) ?></strong> &bull; Size: <strong><?= $totalSizeKb ?> KB</strong>
                </div>
            </div>
        </div>

        <a href="<?= baseUrl('admin/backup.php?action=export') ?>" class="btn btn-primary" style="padding:0.65rem 1.25rem;">
            <span>&#11015;</span> Download Full Database Backup (.sql)
        </a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
        <!-- Backup Action Guide -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>&#128190;</span> Export SQL Backup
                </div>
            </div>
            <div class="card-body">
                <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.5;margin-bottom:1rem;">
                    Creates a complete, portable SQL dump containing all schema structures and table rows including users, inventory, customers, PC repair tickets, invoices, and payments.
                </p>
                <div style="background:#f8fafc;padding:0.75rem 1rem;border-radius:var(--radius-sm);border:1px solid var(--border-light);font-size:0.8rem;margin-bottom:1.25rem;">
                    &#128161; <strong>Best Practice:</strong> Perform daily backups before closing the shop to safeguard all repair and sales transactions.
                </div>
                <a href="<?= baseUrl('admin/backup.php?action=export') ?>" class="btn btn-navy" style="width:100%;text-align:center;">
                    Export Now (.sql)
                </a>
            </div>
        </div>

        <!-- Restore Database Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>&#128257;</span> Restore Database
                </div>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('WARNING: Restoring will overwrite existing records with the backup file data. Are you sure you want to proceed?');">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="restore">

                    <p style="font-size:0.85rem;color:var(--text-secondary);line-height:1.45;margin-bottom:1rem;">
                        Upload a previously generated <code>.sql</code> backup file to restore system data.
                    </p>

                    <div class="form-group">
                        <label class="form-label" for="sql_file">Choose .SQL Backup File <span class="required">*</span></label>
                        <input type="file" id="sql_file" name="sql_file" class="form-control" accept=".sql" required>
                    </div>

                    <button type="submit" class="btn btn-danger" style="width:100%;">
                        <span>&#9888;</span> Restore from Backup File
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tables Breakdown -->
    <div class="card" style="margin-top:1.5rem;">
        <div class="card-header">
            <div class="card-title">
                <span>&#128202;</span> Database Tables Overview
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th style="text-align:center;">Rows</th>
                        <th style="text-align:right;">Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableStats as $ts): ?>
                        <tr>
                            <td><code><?= e($ts['table_name']) ?></code></td>
                            <td style="text-align:center;"><strong><?= $ts['table_rows'] ?></strong></td>
                            <td style="text-align:right;color:var(--text-muted);"><?= $ts['size_kb'] ?> KB</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
