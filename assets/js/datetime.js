// تاریخ و ساعت به زبان دری افغانستان
class DariDateTime {
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
    this.weekdays = [
      "یکشنبه",
      "دوشنبه",
      "سه‌شنبه",
      "چهارشنبه",
      "پنج‌شنبه",
      "جمعه",
      "شنبه",
    ];
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

    let jm, jd;
    if (days < 186) {
      jm = 1 + Math.floor(days / 31);
      jd = 1 + (days % 31);
    } else {
      jm = 7 + Math.floor((days - 186) / 30);
      jd = 1 + ((days - 186) % 30);
    }

    return [jy, jm, jd];
  }

  formatDateTime() {
    const now = new Date();
    const [jy, jm, jd] = this.gregorianToJalali(
      now.getFullYear(),
      now.getMonth() + 1,
      now.getDate()
    );

    const weekday = this.weekdays[now.getDay()];
    const month = this.months[jm - 1];

    // فرمت ساعت 12 ساعته
    let hours = now.getHours();
    const ampm = hours >= 12 ? "ب.ظ" : "ق.ظ";
    hours = hours % 12;
    hours = hours ? hours : 12; // ساعت 0 باید 12 باشد
    const minutes = now.getMinutes().toString().padStart(2, "0");
    const seconds = now.getSeconds().toString().padStart(2, "0");
    const time = `${hours}:${minutes}:${seconds} ${ampm}`;

    return {
      date: `${jd} ${month} ${jy}`,
      time: time,
      weekday: weekday,
      full: `${weekday}، ${jd} ${month} ${jy} - ${time}`,
    };
  }

  updateDisplay() {
    const datetime = this.formatDateTime();
    const element = document.getElementById("dari-datetime");
    if (element) {
      element.innerHTML = `
                <span class="weekday">${datetime.weekday}</span>
                <span class="date">${datetime.date}</span>
                <span class="time">${datetime.time}</span>
            `;
    }
  }

  init() {
    this.updateDisplay();
    setInterval(() => this.updateDisplay(), 1000);
  }
}

// راه‌اندازی خودکار
document.addEventListener("DOMContentLoaded", function () {
  const dariDateTime = new DariDateTime();
  dariDateTime.init();
});
