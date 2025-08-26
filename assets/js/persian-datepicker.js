// تقویم شمسی ساده
class PersianDatePicker {
  constructor() {
    this.months = [
      "حمل",
      "ثور",
      "جوزا",
      "سرطان",
      "اسد",
      "سنبله",
      "میزان",
      "عقرب",
      "قوس",
      "جدی",
      "دلو",
      "حوت",
    ];
    this.weekDays = ["ش", "ی", "د", "س", "چ", "پ", "ج"];
    this.init();
  }

  init() {
    document.addEventListener("DOMContentLoaded", () => {
      this.initDateInputs();
    });
  }

  initDateInputs() {
    const dateInputs = document.querySelectorAll(
      'input[type="date"], .persian-date'
    );
    dateInputs.forEach((input) => {
      this.convertToPersianDatePicker(input);
    });
  }

  convertToPersianDatePicker(input) {
    // تبدیل input به text
    input.type = "text";
    input.placeholder = "YYYY/MM/DD";
    input.classList.add("persian-datepicker");

    // اضافه کردن آیکون تقویم
    const wrapper = document.createElement("div");
    wrapper.className = "date-input-wrapper";
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const icon = document.createElement("i");
    icon.className = "fas fa-calendar-alt date-icon";
    wrapper.appendChild(icon);

    // اضافه کردن event listener
    input.addEventListener("click", () => this.showDatePicker(input));
    icon.addEventListener("click", () => this.showDatePicker(input));

    // تبدیل تاریخ موجود
    if (input.value) {
      input.value = this.convertToJalali(input.value);
    }
  }

  showDatePicker(input) {
    // حذف تقویم قبلی
    const existingPicker = document.querySelector(".persian-calendar");
    if (existingPicker) {
      existingPicker.remove();
    }

    const calendar = this.createCalendar(input);
    document.body.appendChild(calendar);

    // موقعیت تقویم
    const rect = input.getBoundingClientRect();
    calendar.style.top = rect.bottom + window.scrollY + 5 + "px";
    calendar.style.left = rect.left + "px";

    // بستن با کلیک خارج
    setTimeout(() => {
      document.addEventListener(
        "click",
        (e) => {
          if (!calendar.contains(e.target) && e.target !== input) {
            calendar.remove();
          }
        },
        { once: true }
      );
    }, 100);
  }

  createCalendar(input) {
    const calendar = document.createElement("div");
    calendar.className = "persian-calendar";

    const today = new Date();
    const jalaliToday = this.gregorianToJalali(
      today.getFullYear(),
      today.getMonth() + 1,
      today.getDate()
    );

    let currentYear = jalaliToday[0];
    let currentMonth = jalaliToday[1];

    // اگر تاریخی در input وجود دارد
    if (input.value) {
      const parts = input.value.split("/");
      if (parts.length === 3) {
        currentYear = parseInt(parts[0]);
        currentMonth = parseInt(parts[1]);
      }
    }

    calendar.innerHTML = this.generateCalendarHTML(
      currentYear,
      currentMonth,
      input
    );
    return calendar;
  }

  generateCalendarHTML(year, month, input) {
    const daysInMonth = month <= 6 ? 31 : month <= 11 ? 30 : 29;
    const firstDayOfMonth = this.getFirstDayOfJalaliMonth(year, month);

    let html = `
            <div class="calendar-header">
                <button type="button" class="nav-btn prev-month" data-year="${year}" data-month="${month}">&lt;</button>
                <span class="current-month">${
                  this.months[month - 1]
                } ${year}</span>
                <button type="button" class="nav-btn next-month" data-year="${year}" data-month="${month}">&gt;</button>
            </div>
            <div class="calendar-weekdays">
        `;

    this.weekDays.forEach((day) => {
      html += `<div class="weekday">${day}</div>`;
    });

    html += '</div><div class="calendar-days">';

    // روزهای خالی ابتدای ماه
    for (let i = 0; i < firstDayOfMonth; i++) {
      html += '<div class="day empty"></div>';
    }

    // روزهای ماه
    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = `${year}/${month.toString().padStart(2, "0")}/${day
        .toString()
        .padStart(2, "0")}`;
      html += `<div class="day" data-date="${dateStr}">${day}</div>`;
    }

    html += "</div>";

    // اضافه کردن event listeners
    setTimeout(() => {
      const calendar = document.querySelector(".persian-calendar");

      // انتخاب روز
      calendar.querySelectorAll(".day:not(.empty)").forEach((dayEl) => {
        dayEl.addEventListener("click", () => {
          input.value = dayEl.dataset.date;
          calendar.remove();
          input.dispatchEvent(new Event("change"));
        });
      });

      // ناوبری ماه
      calendar.querySelector(".prev-month")?.addEventListener("click", (e) => {
        let newMonth = parseInt(e.target.dataset.month) - 1;
        let newYear = parseInt(e.target.dataset.year);
        if (newMonth < 1) {
          newMonth = 12;
          newYear--;
        }
        calendar.innerHTML = this.generateCalendarHTML(
          newYear,
          newMonth,
          input
        );
      });

      calendar.querySelector(".next-month")?.addEventListener("click", (e) => {
        let newMonth = parseInt(e.target.dataset.month) + 1;
        let newYear = parseInt(e.target.dataset.year);
        if (newMonth > 12) {
          newMonth = 1;
          newYear++;
        }
        calendar.innerHTML = this.generateCalendarHTML(
          newYear,
          newMonth,
          input
        );
      });
    }, 10);

    return html;
  }

  getFirstDayOfJalaliMonth(jYear, jMonth) {
    const gregorian = this.jalaliToGregorian(jYear, jMonth, 1);
    const date = new Date(gregorian[0], gregorian[1] - 1, gregorian[2]);
    return (date.getDay() + 1) % 7; // تنظیم برای شنبه = 0
  }

  gregorianToJalali(gy, gm, gd) {
    const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

    let jy = gy <= 1600 ? 0 : 979;
    gy -= gy <= 1600 ? 621 : 1600;

    const gy2 = gm > 2 ? gy + 1 : gy;
    let days =
      365 * gy +
      Math.floor((gy2 + 3) / 4) -
      Math.floor((gy2 + 99) / 100) +
      Math.floor((gy2 + 399) / 400) -
      80 +
      gd +
      g_d_m[gm - 1];

    jy += 33 * Math.floor(days / 12053);
    days %= 12053;

    jy += 4 * Math.floor(days / 1461);
    days %= 1461;

    if (days > 365) {
      jy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }

    const jm =
      days < 186
        ? 1 + Math.floor(days / 31)
        : 7 + Math.floor((days - 186) / 30);
    const jd = days < 186 ? 1 + (days % 31) : 1 + ((days - 186) % 30);

    return [jy, jm, jd];
  }

  jalaliToGregorian(jy, jm, jd) {
    let gy = jy <= 979 ? 621 : 1600;
    jy -= jy <= 979 ? 0 : 979;

    const jp = jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186;
    let days =
      365 * jy +
      Math.floor(jy / 33) * 8 +
      Math.floor(((jy % 33) + 3) / 4) +
      78 +
      jd +
      jp;

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

    const sal_a = [
      0,
      31,
      leap ? 29 : 28,
      31,
      30,
      31,
      30,
      31,
      31,
      30,
      31,
      30,
      31,
    ];
    let gm = 0;
    while (gm < 13 && days >= sal_a[gm]) {
      days -= sal_a[gm];
      gm++;
    }

    return [gy, gm, days + 1];
  }

  convertToJalali(gregorianDate) {
    if (!gregorianDate) return "";
    const parts = gregorianDate.split("-");
    if (parts.length !== 3) return gregorianDate;

    const jalali = this.gregorianToJalali(
      parseInt(parts[0]),
      parseInt(parts[1]),
      parseInt(parts[2])
    );
    return `${jalali[0]}/${jalali[1].toString().padStart(2, "0")}/${jalali[2]
      .toString()
      .padStart(2, "0")}`;
  }

  convertToGregorian(jalaliDate) {
    if (!jalaliDate) return "";
    const parts = jalaliDate.split("/");
    if (parts.length !== 3) return jalaliDate;

    const gregorian = this.jalaliToGregorian(
      parseInt(parts[0]),
      parseInt(parts[1]),
      parseInt(parts[2])
    );
    return `${gregorian[0]}-${gregorian[1]
      .toString()
      .padStart(2, "0")}-${gregorian[2].toString().padStart(2, "0")}`;
  }
}

// راه‌اندازی تقویم
new PersianDatePicker();
