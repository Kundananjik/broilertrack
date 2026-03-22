<?php
if (!defined('BROILERTRACK_VERSION')) {
    define('BROILERTRACK_VERSION', '1.0');
}
$currentYear = date('Y');
$serverTime = date('Y-m-d H:i:s');
$serverUtc = gmdate('Y-m-d\TH:i:s\Z');
?>
<footer class="app-footer">
    <div class="footer-meta">
        <p class="footer-title">BroilerTrack Management System | Version <?= htmlspecialchars(BROILERTRACK_VERSION, ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Developed by Kundananji Simukonda</p>
        <p>Email: <a href="mailto:kundananjisimukonda@gmail.com">kundananjisimukonda@gmail.com</a> | Phone: <a href="tel:+260971863462">+260 97 186 3462</a></p>
        <p>Copyright &copy; <?= htmlspecialchars($currentYear, ENT_QUOTES, 'UTF-8'); ?> Kundananji Simukonda. All rights reserved.</p>
        <p>Server Time: <span id="server-time" data-utc="<?= htmlspecialchars($serverUtc, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($serverTime, ENT_QUOTES, 'UTF-8'); ?></span></p>
    </div>
</footer>
<script>
(function () {
    var el = document.getElementById('server-time');
    if (!el) {
        return;
    }

    var utcText = el.getAttribute('data-utc') || '';
    var seed = new Date(utcText);
    if (Number.isNaN(seed.getTime())) {
        return;
    }

    var baseUtcMs = seed.getTime();
    var baseClientMs = Date.now();

    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function formatLocal(date) {
        return date.getFullYear() + '-' +
            pad(date.getMonth() + 1) + '-' +
            pad(date.getDate()) + ' ' +
            pad(date.getHours()) + ':' +
            pad(date.getMinutes()) + ':' +
            pad(date.getSeconds());
    }

    function tick() {
        var elapsedMs = Date.now() - baseClientMs;
        var localDate = new Date(baseUtcMs + elapsedMs);
        el.textContent = formatLocal(localDate);
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
