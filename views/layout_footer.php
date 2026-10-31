    </main>
</div>
<?php require __DIR__ . '/footer_content.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script>
    (function () {
        var toggle = document.getElementById('themeToggle');
        if (!toggle) {
            return;
        }

        function currentTheme() {
            return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        }

        function applyButtonState() {
            var theme = currentTheme();
            var isDark = theme === 'dark';
            toggle.textContent = isDark ? 'Light Mode' : 'Dark Mode';
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        }

        applyButtonState();
        toggle.addEventListener('click', function () {
            var nextTheme = currentTheme() === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nextTheme);
            try {
                localStorage.setItem('bt_theme', nextTheme);
            } catch (e) {}
            applyButtonState();
        });
    })();
</script>
</body>
</html>
