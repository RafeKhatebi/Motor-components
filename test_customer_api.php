<?php
/**
 * تست API مشتریان
 */
require_once 'init_security.php';

// فقط ادمین
if (!isset($_SESSION['user_id'])) {
    die('لطفاً ابتدا وارد شوید');
}

echo "<h2>🧪 تست API مشتریان</h2>";

// تولید CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
body { font-family: Tahoma; margin: 20px; }
.test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
.result { padding: 10px; margin: 5px 0; border-radius: 3px; }
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }
</style>

<div class="test-section">
    <h3>1. تست بررسی شماره تلفن</h3>
    <button onclick="testCheckPhone()">تست check_phone.php</button>
    <div id="phoneResult"></div>
</div>

<div class="test-section">
    <h3>2. تست افزودن مشتری</h3>
    <button onclick="testAddCustomer()">تست add_customer.php</button>
    <div id="addResult"></div>
</div>

<div class="test-section">
    <h3>3. تست دریافت اطلاعات مشتری</h3>
    <button onclick="testGetCustomer()">تست get_customer.php</button>
    <div id="getResult"></div>
</div>

<script>
async function testCheckPhone() {
    const resultDiv = document.getElementById('phoneResult');
    resultDiv.innerHTML = 'در حال تست...';
    
    try {
        const formData = new FormData();
        formData.append('phone', '0798123456');
        formData.append('table', 'customers');
        
        const response = await fetch('api/check_phone.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = `<div class="result success">✅ موفق: ${result.message}</div>`;
        } else {
            resultDiv.innerHTML = `<div class="result error">❌ خطا: ${result.message}</div>`;
        }
    } catch (error) {
        resultDiv.innerHTML = `<div class="result error">❌ خطای شبکه: ${error.message}</div>`;
    }
}

async function testAddCustomer() {
    const resultDiv = document.getElementById('addResult');
    resultDiv.innerHTML = 'در حال تست...';
    
    try {
        const formData = new FormData();
        formData.append('name', 'مشتری تست');
        formData.append('phone', '0798' + Math.floor(Math.random() * 1000000));
        formData.append('address', 'آدرس تست');
        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
        
        const response = await fetch('api/add_customer.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = `<div class="result success">✅ موفق: ${result.message}</div>`;
        } else {
            resultDiv.innerHTML = `<div class="result error">❌ خطا: ${result.message}</div>`;
        }
    } catch (error) {
        resultDiv.innerHTML = `<div class="result error">❌ خطای شبکه: ${error.message}</div>`;
    }
}

async function testGetCustomer() {
    const resultDiv = document.getElementById('getResult');
    resultDiv.innerHTML = 'در حال تست...';
    
    try {
        const response = await fetch('api/get_customer.php?id=1');
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = `<div class="result success">✅ موفق: مشتری "${result.data.name}" یافت شد</div>`;
        } else {
            resultDiv.innerHTML = `<div class="result error">❌ خطا: ${result.message}</div>`;
        }
    } catch (error) {
        resultDiv.innerHTML = `<div class="result error">❌ خطای شبکه: ${error.message}</div>`;
    }
}
</script>

<p><a href="customers.php" style="background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;">🔙 بازگشت به صفحه مشتریان</a></p>