-- فاز 3: سیستم گارانتی
-- Phase 3: Warranty System

-- 1. جدول گارانتی محصولات
CREATE TABLE warranties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_item_id INT NOT NULL,
    product_id INT NOT NULL,
    customer_id INT,
    warranty_start DATE NOT NULL,
    warranty_end DATE NOT NULL,
    warranty_months INT NOT NULL,
    serial_number VARCHAR(100),
    warranty_type ENUM('manufacturer', 'shop', 'extended') DEFAULT 'shop',
    status ENUM('active', 'expired', 'claimed', 'void') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_item_id) REFERENCES sale_items(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- 2. جدول درخواست گارانتی
CREATE TABLE warranty_claims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    warranty_id INT NOT NULL,
    claim_date DATE NOT NULL,
    issue_description TEXT NOT NULL,
    claim_type ENUM('repair', 'replace', 'refund') NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    resolution TEXT,
    cost DECIMAL(10,2) DEFAULT 0,
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warranty_id) REFERENCES warranties(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- 3. جدول تاریخچه گارانتی
CREATE TABLE warranty_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    warranty_id INT NOT NULL,
    action ENUM('created', 'claimed', 'repaired', 'replaced', 'expired', 'voided') NOT NULL,
    description TEXT,
    performed_by INT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warranty_id) REFERENCES warranties(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- 4. ایندکسهای بهینه
CREATE INDEX idx_warranties_product ON warranties(product_id);
CREATE INDEX idx_warranties_customer ON warranties(customer_id);
CREATE INDEX idx_warranties_status ON warranties(status);
CREATE INDEX idx_warranties_dates ON warranties(warranty_start, warranty_end);
CREATE INDEX idx_warranty_claims_status ON warranty_claims(status);
CREATE INDEX idx_warranty_claims_date ON warranty_claims(claim_date);