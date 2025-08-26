-- جدول انواع تراکنش‌ها
CREATE TABLE IF NOT EXISTS transaction_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('expense', 'withdrawal') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول تراکنش‌های مالی
CREATE TABLE IF NOT EXISTS financial_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(20) UNIQUE NOT NULL,
    transaction_type ENUM('expense', 'withdrawal') NOT NULL,
    type_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    person_name VARCHAR(255) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES transaction_types(id) ON DELETE CASCADE
);

-- درج انواع پیش‌فرض تراکنش‌ها
INSERT INTO transaction_types (name, type) VALUES
('خرید کالا', 'expense'),
('حقوق پرسنل', 'expense'),
('اجاره مغازه', 'expense'),
('قبض برق', 'expense'),
('قبض آب', 'expense'),
('قبض گاز', 'expense'),
('تعمیرات', 'expense'),
('حمل و نقل', 'expense'),
('تبلیغات', 'expense'),
('سایر هزینه‌ها', 'expense'),
('برداشت شخصی', 'withdrawal'),
('وام', 'withdrawal'),
('سرمایه‌گذاری', 'withdrawal'),
('سایر برداشت‌ها', 'withdrawal');