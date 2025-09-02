-- ارتقاء دیتابیس برای سیستم پرزه فروشی موتور
-- Motor Parts Shop Database Upgrade

-- 1. ارتقاء جدول محصولات
ALTER TABLE products 
ADD COLUMN oem_number VARCHAR(100) AFTER code,
ADD COLUMN brand VARCHAR(100) AFTER oem_number,
ADD COLUMN motor_model VARCHAR(100) AFTER brand,
ADD COLUMN year_from INT AFTER motor_model,
ADD COLUMN year_to INT AFTER year_from,
ADD COLUMN weight DECIMAL(8,2) AFTER year_to,
ADD COLUMN dimensions VARCHAR(100) AFTER weight,
ADD COLUMN warranty_months INT DEFAULT 0 AFTER dimensions,
ADD COLUMN shelf_location VARCHAR(50) AFTER warranty_months,
ADD COLUMN barcode VARCHAR(100) UNIQUE AFTER shelf_location,
ADD COLUMN image_path VARCHAR(255) AFTER barcode,
ADD COLUMN part_type ENUM('original', 'aftermarket', 'used') DEFAULT 'aftermarket' AFTER image_path,
ADD COLUMN country_origin VARCHAR(50) AFTER part_type;

-- 2. جدول برندهای موتور
CREATE TABLE motor_brands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    logo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. جدول مدلهای موتور
CREATE TABLE motor_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    brand_id INT,
    name VARCHAR(100) NOT NULL,
    year_from INT,
    year_to INT,
    engine_size VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES motor_brands(id)
);

-- 4. جدول قطعات سازگار
CREATE TABLE compatible_parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    main_product_id INT,
    compatible_product_id INT,
    compatibility_type ENUM('exact', 'alternative', 'upgrade') DEFAULT 'alternative',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (main_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (compatible_product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 5. جدول تصاویر محصولات (چندین تصویر برای هر محصول)
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    alt_text VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 6. درج برندهای پایه
INSERT INTO motor_brands (name) VALUES 
('Honda'), ('Yamaha'), ('Suzuki'), ('Kawasaki'), 
('Bajaj'), ('TVS'), ('Hero'), ('Royal Enfield');

-- 7. ایجاد ایندکسهای بهینه
CREATE INDEX idx_products_oem ON products(oem_number);
CREATE INDEX idx_products_brand ON products(brand);
CREATE INDEX idx_products_model ON products(motor_model);
CREATE INDEX idx_products_barcode ON products(barcode);
CREATE INDEX idx_compatible_main ON compatible_parts(main_product_id);
CREATE INDEX idx_compatible_alt ON compatible_parts(compatible_product_id);