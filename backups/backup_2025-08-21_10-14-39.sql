-- Backup created on 2025-08-21 10:14:39

-- Table: users
DELETE FROM users;
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('8', 'admin', '$2y$10$AdhGNu1BidvDidK9ZYkSd.RiJYsLZFTRdymm00AyFhwS4nxfBOohq', 'مدیر اصلی سیستم', 'admin', '2025-08-18 13:50:19');
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('9', 'worker', '$2y$10$GMKxjyw/521R9XcmUWlMZOPkrzLYbzfM8bsKzZ2KSjmeE0vBKGdjG', 'worker', 'employee', '2025-08-18 13:57:51');
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('10', 'manger', '$2y$10$Fx9VPZTmzi5u2CNwyyZ4Ce.EjJsvvTkHHlp1paFKTZ1nnWNdCpnwq', 'Nasir ahmad', 'manager', '2025-08-19 22:18:01');

-- Table: categories
DELETE FROM categories;
INSERT INTO categories (id, name, description, created_at) VALUES ('1', 'موتور لمر', 'درجه یک', '2025-08-17 17:24:11');
INSERT INTO categories (id, name, description, created_at) VALUES ('2', 'موتور اوندا', 'اوندا  جدید', '2025-08-18 12:15:52');
INSERT INTO categories (id, name, description, created_at) VALUES ('4', 'سچرخ', 'ننن', '2025-08-19 22:06:53');
INSERT INTO categories (id, name, description, created_at) VALUES ('5', 'غلام سرور ', '123', '2025-08-20 10:48:30');
INSERT INTO categories (id, name, description, created_at) VALUES ('6', 'موتر 70', 'دو زینه', '2025-08-21 12:33:26');

-- Table: products
DELETE FROM products;
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('1', 'قاب زنحیر', '1', 'PRD-0001', '150.00', '220.00', '3', '5', 'ندارد', '2025-08-17 17:24:52');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('2', 'گیر بکس', '2', 'PRD-0002', '120.00', '160.00', '3', '5', 'درجه یک', '2025-08-18 12:18:03');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('3', 'کریم شاه', '2', '0001', '122.00', '132.00', '12', '5', '', '2025-08-18 13:28:33');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('4', 'یی', NULL, '0002', '212.00', '212.00', '12', '5', NULL, '2025-08-18 13:29:05');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('5', 'چراغ موتر لمر1', '1', '0003', '120.00', '150.00', '1', '5', 'ندارد', '2025-08-19 22:06:31');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('6', 'سیم کلچ', '2', '0004', '40.00', '50.00', '17', '5', 'ندارد', '2025-08-21 12:35:19');

-- Table: customers
DELETE FROM customers;
INSERT INTO customers (id, name, phone, address, created_at) VALUES ('1', 'علی احمد', '0728958423', 'Herat, Afghanistan', '2025-08-17 16:58:29');
INSERT INTO customers (id, name, phone, address, created_at) VALUES ('2', 'شاه میر', '0798999789', 'هرات چشت', '2025-08-18 12:22:36');
INSERT INTO customers (id, name, phone, address, created_at) VALUES ('3', 'نصیر احمد', '0798944187', 'هرات گذره', '2025-08-19 22:07:31');
INSERT INTO customers (id, name, phone, address, created_at) VALUES ('4', 'محمد جدید', '0721958423', 'هرات', '2025-08-20 13:48:20');

-- Table: suppliers
DELETE FROM suppliers;
INSERT INTO suppliers (id, name, phone, address, created_at) VALUES ('1', 'Rafe Ahmad Khatebi', '0728958423', 'Herat, Afghanistan\r\nHerat', '2025-08-17 17:48:54');
INSERT INTO suppliers (id, name, phone, address, created_at) VALUES ('2', 'غلام سرور', '0721158223', 'Herat, Afghanistan\r\nHerat', '2025-08-18 12:25:35');

-- Table: sales
DELETE FROM sales;
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('3', NULL, '440.00', '0.00', '440.00', 'credit', '0.00', '140.00', 'partial', '2025-08-17 17:35:23', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('4', NULL, '1100.00', '55.00', '1045.00', 'cash', '1045.00', '0.00', 'paid', '2025-08-17 17:52:10', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('5', '1', '660.00', '0.00', '660.00', 'cash', '660.00', '0.00', 'paid', '2025-08-17 18:35:16', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('7', NULL, '320.00', '0.00', '320.00', 'cash', '320.00', '0.00', 'paid', '2025-08-18 12:24:05', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('8', '2', '440.00', '0.00', '440.00', 'cash', '440.00', '0.00', 'paid', '2025-08-18 12:24:29', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('10', NULL, '220.00', '0.00', '220.00', 'cash', '220.00', '0.00', 'paid', '2025-08-19 22:09:16', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('11', '3', '520.00', '0.00', '520.00', 'cash', '520.00', '0.00', 'paid', '2025-08-19 22:09:44', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('12', '3', '300.00', '15.00', '285.00', 'cash', '285.00', '0.00', 'paid', '2025-08-20 10:53:31', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('13', NULL, '50.00', '0.00', '50.00', 'cash', '50.00', '0.00', 'paid', '2025-08-21 12:35:59', 'completed', NULL, NULL, NULL);

-- Table: sale_items
DELETE FROM sale_items;
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('3', '3', '1', '1', '220.00', '220.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('4', '3', '1', '1', '220.00', '220.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('5', '4', '1', '5', '220.00', '1100.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('6', '5', '1', '3', '220.00', '660.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('7', '7', '2', '2', '160.00', '320.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('10', '8', '1', '2', '220.00', '440.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('11', '10', '1', '1', '220.00', '220.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('12', '11', '1', '1', '220.00', '220.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('13', '11', '5', '2', '150.00', '300.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('14', '12', '5', '2', '150.00', '300.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('15', '13', '6', '1', '50.00', '50.00');

-- Table: purchases
DELETE FROM purchases;
INSERT INTO purchases (id, supplier_id, total_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('1', '1', '1800.00', 'cash', '1800.00', '0.00', 'paid', '2025-08-17 17:49:38', 'completed', NULL, NULL, NULL);
INSERT INTO purchases (id, supplier_id, total_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('2', '2', '480.00', 'cash', '360.00', '0.00', 'paid', '2025-08-18 12:20:52', 'completed', NULL, NULL, NULL);
INSERT INTO purchases (id, supplier_id, total_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('3', '2', '2544.00', 'cash', '2544.00', '0.00', 'paid', '2025-08-18 13:29:05', 'completed', NULL, NULL, NULL);

-- Table: purchase_items
DELETE FROM purchase_items;
INSERT INTO purchase_items (id, purchase_id, product_id, quantity, unit_price, total_price) VALUES ('1', '1', '1', '12', '150.00', '1800.00');
INSERT INTO purchase_items (id, purchase_id, product_id, quantity, unit_price, total_price) VALUES ('3', '2', '1', '4', '120.00', '480.00');
INSERT INTO purchase_items (id, purchase_id, product_id, quantity, unit_price, total_price) VALUES ('4', '3', '4', '12', '212.00', '2544.00');

