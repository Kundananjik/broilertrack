<?php
if (!defined('BROILERTRACK_VERSION')) {
    define('BROILERTRACK_VERSION', '1.0');
}
$currentYear = date('Y');
?>
<footer class="app-footer">
    <div class="footer-meta">
        <p class="footer-title">BroilerTrack Management System | Version <?= htmlspecialchars(BROILERTRACK_VERSION, ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Developed by Kundananji Simukonda</p>
        <p>Copyright &copy; <?= htmlspecialchars($currentYear, ENT_QUOTES, 'UTF-8'); ?> Kundananji Simukonda. All rights reserved.</p>
    </div>
</footer>
