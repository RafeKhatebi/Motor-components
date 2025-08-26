<!-- Footer -->
<footer class="footer-minimal">
    <div class="container-fluid">
        <div class="footer-content">
            <div class="footer-left">
                <span>&copy; <?= date('Y') ?> <?= sanitizeOutput(SettingsHelper::getShopName()) ?></span>
            </div>
            <div class="footer-right">
                <a href="dashboard.php">داشبورد</a>
                <a href="reports.php">گزارشات</a>
                <a href="settings.php">تنظیمات</a>
            </div>
        </div>
    </div>
</footer>

<!-- Back to top button -->
<button class="back-to-top" type="button" aria-label="بازگشت به بالا">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Scripts -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/chart.js"></script>
<script src="assets/js/main.js"></script>

<!-- Modernize small UX enhancements (non-intrusive) -->
<script src="assets/js/modernize.js"></script>
<!-- Mobile enhancements -->
<script src="assets/js/mobile-enhancements.js"></script>

<?= $extra_js ?? '' ?>

<script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
</script>
</body>

</html>