// بهبود تجربه آپلود لوگو
document.addEventListener('DOMContentLoaded', function() {
    const logoInput = document.querySelector('input[name="shop_logo"]');
    
    if (logoInput) {
        // پیشنمایش لوگو قبل از آپلود
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // بررسی نوع فایل
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('فرمت فایل مجاز نیست. لطفاً فایل JPG، PNG، GIF یا WEBP انتخاب کنید.');
                    e.target.value = '';
                    return;
                }
                
                // بررسی اندازه فایل (حداکثر 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('حجم فایل نباید بیشتر از 2 مگابایت باشد.');
                    e.target.value = '';
                    return;
                }
                
                // نمایش پیشنمایش
                const reader = new FileReader();
                reader.onload = function(e) {
                    showLogoPreview(e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Drag and drop support
        const logoContainer = logoInput.closest('.form-group');
        if (logoContainer) {
            logoContainer.addEventListener('dragover', function(e) {
                e.preventDefault();
                logoContainer.classList.add('dragover');
            });
            
            logoContainer.addEventListener('dragleave', function(e) {
                e.preventDefault();
                logoContainer.classList.remove('dragover');
            });
            
            logoContainer.addEventListener('drop', function(e) {
                e.preventDefault();
                logoContainer.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    logoInput.files = files;
                    logoInput.dispatchEvent(new Event('change'));
                }
            });
        }
    }
    
    function showLogoPreview(src) {
        // حذف پیشنمایش قبلی
        const existingPreview = document.querySelector('.logo-preview-new');
        if (existingPreview) {
            existingPreview.remove();
        }
        
        // ایجاد پیشنمایش جدید
        const preview = document.createElement('div');
        preview.className = 'logo-preview-new mt-2';
        preview.innerHTML = `
            <label class="form-control-label">پیشنمایش لوگوی جدید</label>
            <div class="logo-preview">
                <img src="${src}" alt="پیشنمایش لوگو">
            </div>
        `;
        
        // اضافه کردن پیشنمایش بعد از input
        const logoInput = document.querySelector('input[name="shop_logo"]');
        logoInput.closest('.form-group').appendChild(preview);
    }
    
    // بهبود فرم ارسال
    const settingsForm = document.querySelector('form[method="POST"]');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            const shopName = document.querySelector('input[name="shop_name"]');
            if (shopName && shopName.value.trim() === '') {
                e.preventDefault();
                alert('نام فروشگاه الزامی است.');
                shopName.focus();
                return false;
            }
            
            // نمایش loading
            const submitBtn = settingsForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ذخیره...';
            }
        });
    }
});