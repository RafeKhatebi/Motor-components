<?php
// Footer for unified system
?>
            </div> <!-- End content-wrapper -->
        </main> <!-- End main-content -->
    </div> <!-- End app-layout -->
    
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; margin: 20px;">
            <i class="fas fa-sign-out-alt" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 15px; color: #1f2937;">تأیید خروج</h3>
            <p style="margin-bottom: 25px; color: #6b7280;">آیا مطمئن هستید که میخواهید از سیستم خارج شوید؟</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button onclick="proceedLogout()" style="background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">تأیید</button>
                <button onclick="cancelLogout()" style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">انصراف</button>
            </div>
        </div>
    </div>

    <!-- Modern Footer -->
    <footer class="modern-footer">
        <div class="footer-content">
            <p>&copy; <?= date('Y') ?> <?= sanitizeOutput(SettingsHelper::getShopName()) ?>. تمامی حقوق محفوظ است.</p>
        </div>
    </footer>

    <!-- Essential Scripts -->
    <script src="assets/js/persian-datepicker.js"></script>
    
    <!-- Unified System JavaScript -->
    <script src="assets/js/unified-system.js"></script>
    
    <!-- Logout Confirmation Script -->
    <script>
    function confirmLogout() {
        document.getElementById('logoutModal').style.display = 'flex';
    }
    
    function cancelLogout() {
        document.getElementById('logoutModal').style.display = 'none';
    }
    
    function proceedLogout() {
        window.location.href = 'logout.php';
    }
    </script>
</body>
</html>