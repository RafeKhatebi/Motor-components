// مدیریت تبدیل تاریخها در فرمها
document.addEventListener('DOMContentLoaded', function() {
    // بررسی تنظیمات تاریخ
    const dateFormat = document.body.dataset.dateFormat || 'gregorian';
    
    if (dateFormat === 'jalali') {
        initPersianDateHandling();
    }
});

function initPersianDateHandling() {
    // تبدیل فیلدهای تاریخ موجود
    convertExistingDates();
    
    // مدیریت ارسال فرمها
    handleFormSubmissions();
    
    // مدیریت فیلترهای تاریخ
    handleDateFilters();
}

function convertExistingDates() {
    // تبدیل تاریخهای نمایش داده شده در جداول
    document.querySelectorAll('.date-display, .datetime-display').forEach(element => {
        const gregorianDate = element.textContent.trim();
        if (gregorianDate && gregorianDate.match(/^\d{4}-\d{2}-\d{2}/)) {
            element.textContent = convertToJalaliDisplay(gregorianDate);
        }
    });
    
    // تبدیل مقادیر input های تاریخ
    document.querySelectorAll('input[type="date"], .date-input').forEach(input => {
        if (input.value && input.value.match(/^\d{4}-\d{2}-\d{2}/)) {
            input.value = convertToJalali(input.value);
        }
    });
}

function handleFormSubmissions() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // تبدیل تاریخهای شمسی به میلادی قبل از ارسال
            const dateInputs = form.querySelectorAll('.persian-datepicker, input[type="date"]');
            
            dateInputs.forEach(input => {
                if (input.value && input.value.match(/^\d{4}\/\d{2}\/\d{2}/)) {
                    // ایجاد input مخفی با تاریخ میلادی
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = input.name + '_gregorian';
                    hiddenInput.value = convertToGregorian(input.value);
                    form.appendChild(hiddenInput);
                    
                    // تغییر نام input اصلی
                    input.name = input.name + '_jalali_display';
                }
            });
        });
    });
}

function handleDateFilters() {
    // مدیریت فیلترهای تاریخ در گزارشات
    document.querySelectorAll('.date-filter').forEach(filter => {
        filter.addEventListener('change', function() {
            if (this.value && this.value.match(/^\d{4}\/\d{2}\/\d{2}/)) {
                // ارسال AJAX با تاریخ تبدیل شده
                const gregorianDate = convertToGregorian(this.value);
                // اینجا میتوان AJAX request ارسال کرد
            }
        });
    });
}

function convertToJalali(gregorianDate) {
    if (!gregorianDate) return '';
    const parts = gregorianDate.split('-');
    if (parts.length !== 3) return gregorianDate;
    
    const jalali = gregorianToJalali(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2]));
    return `${jalali[0]}/${jalali[1].toString().padStart(2, '0')}/${jalali[2].toString().padStart(2, '0')}`;
}

function convertToGregorian(jalaliDate) {
    if (!jalaliDate) return '';
    const parts = jalaliDate.split('/');
    if (parts.length !== 3) return jalaliDate;
    
    const gregorian = jalaliToGregorian(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2]));
    return `${gregorian[0]}-${gregorian[1].toString().padStart(2, '0')}-${gregorian[2].toString().padStart(2, '0')}`;
}

function convertToJalaliDisplay(gregorianDate) {
    const jalaliDate = convertToJalali(gregorianDate);
    if (!jalaliDate) return gregorianDate;
    
    const months = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
    ];
    
    const parts = jalaliDate.split('/');
    if (parts.length === 3) {
        return `${parts[2]} ${months[parseInt(parts[1]) - 1]} ${parts[0]}`;
    }
    
    return jalaliDate;
}

// توابع تبدیل تاریخ (کپی از persian-datepicker.js)
function gregorianToJalali(gy, gm, gd) {
    const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    
    let jy = gy <= 1600 ? 0 : 979;
    gy -= gy <= 1600 ? 621 : 1600;
    
    const gy2 = gm > 2 ? gy + 1 : gy;
    let days = (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + 
               Math.floor((gy2 + 399) / 400) - 80 + gd + g_d_m[gm - 1];
    
    jy += 33 * Math.floor(days / 12053);
    days %= 12053;
    
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    
    if (days > 365) {
        jy += Math.floor((days - 1) / 365);
        days = (days - 1) % 365;
    }
    
    const jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
    const jd = days < 186 ? 1 + (days % 31) : 1 + ((days - 186) % 30);
    
    return [jy, jm, jd];
}

function jalaliToGregorian(jy, jm, jd) {
    let gy = jy <= 979 ? 621 : 1600;
    jy -= jy <= 979 ? 0 : 979;
    
    const jp = jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186;
    let days = (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + 78 + jd + jp;
    
    gy += 400 * Math.floor(days / 146097);
    days %= 146097;
    
    let leap = true;
    if (days >= 36525) {
        days--;
        gy += 100 * Math.floor(days / 36524);
        days %= 36524;
        if (days >= 365) days++;
    }
    
    gy += 4 * Math.floor(days / 1461);
    days %= 1461;
    
    if (days >= 366) {
        leap = false;
        days--;
        gy += Math.floor(days / 365);
        days = days % 365;
    }
    
    const sal_a = [0, 31, (leap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    let gm = 0;
    while (gm < 13 && days >= sal_a[gm]) {
        days -= sal_a[gm];
        gm++;
    }
    
    return [gy, gm, days + 1];
}