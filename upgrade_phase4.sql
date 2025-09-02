-- فاز 4: سیستم بارکد
-- Phase 4: Barcode System

-- 1. جدول بارکدهای محصولات
CREATE TABLE product_barcodes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    barcode_type ENUM('EAN13', 'CODE128', 'QR') DEFAULT 'CODE128',
    is_primary BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 2. جدول تاریخچه اسکن
CREATE TABLE barcode_scans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    barcode VARCHAR(100) NOT NULL,
    product_id INT,
    scan_type ENUM('sale', 'inventory', 'search') NOT NULL,
    scanned_by INT,
    scan_location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (scanned_by) REFERENCES users(id)
);

-- 3. تولید بارکد خودکار برای محصولات موجود
UPDATE products 
SET barcode = CONCAT('MP', LPAD(id, 8, '0')) 
WHERE barcode IS NULL OR barcode = '';

-- 4. درج بارکدها در جدول جدید
INSERT IGNORE INTO product_barcodes (product_id, barcode, barcode_type, is_primary)
SELECT id, barcode, 'CODE128', TRUE 
FROM products 
WHERE barcode IS NOT NULL AND barcode != '';

-- 5. ایندکسهای بهینه
CREATE INDEX idx_product_barcodes_barcode ON product_barcodes(barcode);
CREATE INDEX idx_product_barcodes_product ON product_barcodes(product_id);
CREATE INDEX idx_barcode_scans_barcode ON barcode_scans(barcode);
CREATE INDEX idx_barcode_scans_date ON barcode_scans(created_at);