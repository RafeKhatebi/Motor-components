-- Backup created on 2025-09-06 15:15:48

-- Table: users
DELETE FROM users;
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('8', 'admin', '$2y$10$AdhGNu1BidvDidK9ZYkSd.RiJYsLZFTRdymm00AyFhwS4nxfBOohq', 'مدیر اصلی سیستم', 'admin', '2025-08-18 13:50:19');
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('9', 'worker', '$2y$10$GMKxjyw/521R9XcmUWlMZOPkrzLYbzfM8bsKzZ2KSjmeE0vBKGdjG', 'worker', 'employee', '2025-08-18 13:57:51');
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('10', 'manger', '$2y$10$OTecPVEakgTGKWoclm.dcOAUzIB7sLKFTd34PYuOmuHMrsbsgT93q', 'manger', 'manager', '2025-09-05 19:24:00');
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('11', 'student', '$2y$10$ZwtALJN/f0vAk7bkqs.mYeOVG0ZapgxttHXmTYnCNkdZz5SixnewK', 'شاگرد', 'employee', '2025-09-05 22:00:31');

-- Table: categories
DELETE FROM categories;
INSERT INTO categories (id, name, description, created_at) VALUES ('1', 'موتور لمر', 'درجه یک', '2025-08-17 17:24:11');
INSERT INTO categories (id, name, description, created_at) VALUES ('2', 'موتور اوندا', 'اوندا  جدید', '2025-08-18 12:15:52');
INSERT INTO categories (id, name, description, created_at) VALUES ('4', 'کرولا', 'ندارد', '2025-09-05 20:53:45');
INSERT INTO categories (id, name, description, created_at) VALUES ('5', 'موتور 70 سی سی22', 'ندارد', '2025-09-05 21:03:54');
INSERT INTO categories (id, name, description, created_at) VALUES ('6', 'طیاره', 'صصصص', '2025-09-05 21:07:02');
INSERT INTO categories (id, name, description, created_at) VALUES ('7', 'xxx', 'xxxx', '2025-09-05 21:07:37');
INSERT INTO categories (id, name, description, created_at) VALUES ('8', 'صصص', 'صصص', '2025-09-05 21:08:52');
INSERT INTO categories (id, name, description, created_at) VALUES ('9', 'صص2', '222', '2025-09-05 21:09:03');

-- Table: products
DELETE FROM products;
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, shelf_location, description, created_at) VALUES ('1', 'قاب زنحیر', '1', 'PRD-0001', '150.00', '220.00', '2', '5', NULL, 'ندارد', '2025-08-17 17:24:52');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, shelf_location, description, created_at) VALUES ('2', 'گیر بکس', '2', 'PRD-0002', '120.00', '160.00', '3', '5', NULL, 'درجه یک', '2025-08-18 12:18:03');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, shelf_location, description, created_at) VALUES ('3', 'کریم شاه', '2', '0001', '122.00', '132.00', '12', '5', NULL, '', '2025-08-18 13:28:33');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, shelf_location, description, created_at) VALUES ('4', 'یی', NULL, '0002', '212.00', '212.00', '8', '5', NULL, NULL, '2025-08-18 13:29:05');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, shelf_location, description, created_at) VALUES ('6', 'Rafe Ahmad Khatebi', '7', '0003', '22.00', '32.00', '34', '5', '222', '', '2025-09-05 21:12:29');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, shelf_location, description, created_at) VALUES ('7', 'Ali', '9', '0004', '123.00', '212.00', '24', '5', '212', '', '2025-09-05 21:14:46');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, shelf_location, description, created_at) VALUES ('8', 'غلام سرور', '8', '0005', '123.00', '212.00', '12', '5', 'الف-1', '', '2025-09-05 21:16:35');

-- Table: customers
DELETE FROM customers;
INSERT INTO customers (id, name, phone, address, customer_type, created_at) VALUES ('1', 'علی احمد', '0728958423', 'Herat, Afghanistan', 'retail', '2025-08-17 16:58:29');
INSERT INTO customers (id, name, phone, address, customer_type, created_at) VALUES ('2', 'شاه میر', '0798999789', 'هرات چشت', 'retail', '2025-08-18 12:22:36');
INSERT INTO customers (id, name, phone, address, customer_type, created_at) VALUES ('3', 'Rafe Ahmad Khatebi', '0728958411', 'Herat, Afghanistan', 'retail', '2025-09-05 21:42:24');
INSERT INTO customers (id, name, phone, address, customer_type, created_at) VALUES ('4', 'Kamal', '0733958423', 'Herat, Afghanistan', 'retail', '2025-09-05 21:44:39');

-- Table: suppliers
DELETE FROM suppliers;
INSERT INTO suppliers (id, name, phone, address, created_at) VALUES ('1', 'Rafe Ahmad Khatebi', '0728958423', 'Herat, Afghanistan\r\nHerat', '2025-08-17 17:48:54');
INSERT INTO suppliers (id, name, phone, address, created_at) VALUES ('2', 'غلام سرور', '0721158223', 'Herat, Afghanistan\r\nHerat', '2025-08-18 12:25:35');
INSERT INTO suppliers (id, name, phone, address, created_at) VALUES ('3', 'Rafe Ahmad Khatebi', '0728958000', 'Herat, Afghanistan', '2025-09-05 21:45:58');

-- Table: sales
DELETE FROM sales;
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('3', NULL, '440.00', '0.00', '440.00', 'credit', '0.00', '140.00', 'partial', '2025-08-17 17:35:23', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('4', NULL, '1100.00', '55.00', '1045.00', 'cash', '1045.00', '0.00', 'paid', '2025-08-17 17:52:10', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('5', '1', '660.00', '0.00', '660.00', 'cash', '660.00', '0.00', 'paid', '2025-08-17 18:35:16', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('7', NULL, '320.00', '0.00', '320.00', 'cash', '320.00', '0.00', 'paid', '2025-08-18 12:24:05', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('8', '2', '440.00', '0.00', '440.00', 'cash', '440.00', '0.00', 'paid', '2025-08-18 12:24:29', 'completed', NULL, NULL, NULL);
INSERT INTO sales (id, customer_id, total_amount, discount, final_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('12', '2', '1508.00', '30.16', '1477.84', 'cash', '1477.84', '0.00', 'paid', '2025-09-05 21:25:57', 'completed', NULL, NULL, NULL);

-- Table: sale_items
DELETE FROM sale_items;
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('3', '3', '1', '1', '220.00', '220.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('4', '3', '1', '1', '220.00', '220.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('5', '4', '1', '5', '220.00', '1100.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('6', '5', '1', '3', '220.00', '660.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('7', '7', '2', '2', '160.00', '320.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('10', '8', '1', '2', '220.00', '440.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('14', '12', '4', '4', '212.00', '848.00');
INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price) VALUES ('15', '12', '1', '3', '220.00', '660.00');

-- Table: purchases
DELETE FROM purchases;
INSERT INTO purchases (id, supplier_id, total_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('1', '1', '1800.00', 'cash', '1800.00', '0.00', 'paid', '2025-08-17 17:49:38', 'completed', NULL, NULL, NULL);
INSERT INTO purchases (id, supplier_id, total_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('2', '2', '480.00', 'cash', '360.00', '0.00', 'paid', '2025-08-18 12:20:52', 'completed', NULL, NULL, NULL);
INSERT INTO purchases (id, supplier_id, total_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('3', '2', '2544.00', 'cash', '2544.00', '0.00', 'paid', '2025-08-18 13:29:05', 'completed', NULL, NULL, NULL);
INSERT INTO purchases (id, supplier_id, total_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('4', '1', '1476.00', 'cash', '1476.00', '0.00', 'paid', '2025-09-05 21:26:19', 'completed', NULL, NULL, NULL);
INSERT INTO purchases (id, supplier_id, total_amount, payment_type, paid_amount, remaining_amount, payment_status, created_at, status, return_reason, returned_at, returned_by) VALUES ('5', '2', '10656.00', 'credit', '0.00', '10656.00', 'unpaid', '2025-09-05 21:26:35', 'completed', NULL, NULL, NULL);

-- Table: purchase_items
DELETE FROM purchase_items;
INSERT INTO purchase_items (id, purchase_id, product_id, quantity, unit_price, total_price) VALUES ('1', '1', '1', '12', '150.00', '1800.00');
INSERT INTO purchase_items (id, purchase_id, product_id, quantity, unit_price, total_price) VALUES ('3', '2', '1', '4', '120.00', '480.00');
INSERT INTO purchase_items (id, purchase_id, product_id, quantity, unit_price, total_price) VALUES ('4', '3', '4', '12', '212.00', '2544.00');
INSERT INTO purchase_items (id, purchase_id, product_id, quantity, unit_price, total_price) VALUES ('5', '4', '7', '12', '123.00', '1476.00');
INSERT INTO purchase_items (id, purchase_id, product_id, quantity, unit_price, total_price) VALUES ('6', '5', '6', '32', '333.00', '10656.00');

