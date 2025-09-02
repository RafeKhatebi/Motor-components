-- فاز 2: سیستم قیمتگذاری پیشرفته
-- Phase 2: Advanced Pricing System

-- 1. ارتقاء جدول مشتریان
ALTER TABLE customers 
ADD COLUMN customer_type ENUM('retail', 'wholesale', 'garage', 'dealer') DEFAULT 'retail' AFTER address,
ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0 AFTER customer_type,
ADD COLUMN credit_limit DECIMAL(10,2) DEFAULT 0 AFTER discount_percentage,
ADD COLUMN current_balance DECIMAL(10,2) DEFAULT 0 AFTER credit_limit;

-- 2. جدول قیمتگذاری محصولات
CREATE TABLE product_prices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    customer_type ENUM('retail', 'wholesale', 'garage', 'dealer') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    min_quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_customer_type (product_id, customer_type)
);

-- 3. جدول تخفیفات حجمی
CREATE TABLE volume_discounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    min_quantity INT NOT NULL,
    discount_percentage DECIMAL(5,2) NOT NULL,
    customer_type ENUM('retail', 'wholesale', 'garage', 'dealer'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 4. ایندکسهای بهینه
CREATE INDEX idx_customers_type ON customers(customer_type);
CREATE INDEX idx_product_prices_type ON product_prices(customer_type);
CREATE INDEX idx_volume_discounts_qty ON volume_discounts(min_quantity);