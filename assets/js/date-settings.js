// تابع برای تغییر فوری تنظیمات تاریخ
function changeDateFormat() {
    const dateFormat = document.querySelector('select[name="date_format"]').value;
    
    // نمایش پیام در حال بارگذاری
    const loadingAlert = document.createElement('div');
    loadingAlert.className = 'alert alert-info';
    loadingAlert.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال اعمال تغییرات...';
    document.body.insertBefore(loadingAlert, document.body.firstChild);
    
    // ارسال درخواست AJAX برای بروزرسانی تنظیمات
    const formData = new FormData();
    formData.append('action', 'update_date_format');
    formData.append('date_format', dateFormat);
    
    fetch('api/update_date_format.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loadingAlert.remove();
        
        if (data.success) {
            // نمایش پیام موفقیت
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success alert-dismissible fade show';
            successAlert.innerHTML = `
                <i class="fas fa-check-circle"></i> تنظیمات تاریخ با موفقیت تغییر یافت
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.insertBefore(successAlert, document.body.firstChild);
            
            // بروزرسانی تاریخها در صفحه
            updateDatesOnPage(dateFormat);
        } else {
            // نمایش پیام خطا
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger alert-dismissible fade show';
            errorAlert.innerHTML = `
                <i class="fas fa-exclamation-circle"></i> خطا در تغییر تنظیمات
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.insertBefore(errorAlert, document.body.firstChild);
        }
    })
    .catch(error => {
        loadingAlert.remove();
        console.error('Error:', error);
    });
}

// تابع برای بروزرسانی تاریخها در صفحه
function updateDatesOnPage(format) {
    // این تابع می‌تواند تاریخهای موجود در صفحه را بروزرسانی کند
    // برای سادگی، صفحه را reload می‌کنیم
    setTimeout(() => {
        location.reload();
    }, 1500);
}

// اضافه کردن event listener به select تنظیمات تاریخ
document.addEventListener('DOMContentLoaded', function() {
    const dateFormatSelect = document.querySelector('select[name="date_format"]');
    if (dateFormatSelect) {
        dateFormatSelect.addEventListener('change', changeDateFormat);
    }
});