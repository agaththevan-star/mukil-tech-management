<?php
/**
 * MUKIL TECH - Main Footer Template
 */
$businessName = getSetting('business_name', 'MUKIL TECH');
$address = getSetting('address', 'Vivekanandanagar, Kilinochchi, Sri Lanka');
$phone = getSetting('phone', '077 479 5253 / 075 531 6840');
$email = getSetting('email', 'mukiltech7@gmail.com');
?>
<?php
// Flush output buffer started in header.php to send buffered HTML now.
if (function_exists('ob_get_level') && ob_get_level() > 0) {
    @ob_end_flush();
}

?>

    </main>

        <footer class="no-print" style="margin-top:auto;padding:1.25rem 1.75rem;border-top:1px solid var(--border-light);background:var(--bg-card);display:flex;justify-content:space-between;align-items:center;font-size:0.8rem;color:var(--text-secondary);flex-wrap:wrap;gap:0.75rem;">
            <div>
                <strong><?= e($businessName) ?></strong> &bull; <?= e($address) ?>
            </div>
            <div>
                Phone: <strong><?= e($phone) ?></strong> | Email: <strong><?= e($email) ?></strong>
            </div>
            <div>
                &copy; <?= date('Y') ?> MUKIL TECH. All Rights Reserved.
            </div>
        </footer>
    </div>
</div>

<script>
// Small vanilla browser helpers (minimal JavaScript as permitted by prompt)
document.addEventListener('DOMContentLoaded', function() {
    // Set initial icon
    var themeIcon = document.getElementById('theme-icon');
    if (themeIcon) {
        themeIcon.innerHTML = document.documentElement.classList.contains('dark-theme') ? '&#9728;' : '&#127769;'; // Sun or Moon
    }
});

function toggleTheme() {
    var isDark = document.documentElement.classList.toggle('dark-theme');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    var themeIcon = document.getElementById('theme-icon');
    if (themeIcon) {
        themeIcon.innerHTML = isDark ? '&#9728;' : '&#127769;'; // Sun or Moon
    }
}
</script>
</body>
</html>
