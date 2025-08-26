-- Test Backup created on 2025-08-20 11:07:38

-- Table: users (3 records)
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('8', 'admin', '$2y$10$AdhGNu1BidvDidK9ZYkSd.RiJYsLZFTRdymm00AyFhwS4nxfBOohq', 'مدیر اصلی سیستم', 'admin', '2025-08-18 13:50:19');
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('9', 'worker', '$2y$10$GMKxjyw/521R9XcmUWlMZOPkrzLYbzfM8bsKzZ2KSjmeE0vBKGdjG', 'worker', 'employee', '2025-08-18 13:57:51');
INSERT INTO users (id, username, password, full_name, role, created_at) VALUES ('10', 'manger', '$2y$10$Fx9VPZTmzi5u2CNwyyZ4Ce.EjJsvvTkHHlp1paFKTZ1nnWNdCpnwq', 'Nasir ahmad', 'manager', '2025-08-19 22:18:01');

-- Table: categories (4 records)
INSERT INTO categories (id, name, description, created_at) VALUES ('1', 'موتور لمر', 'درجه یک', '2025-08-17 17:24:11');
INSERT INTO categories (id, name, description, created_at) VALUES ('2', 'موتور اوندا', 'اوندا  جدید', '2025-08-18 12:15:52');
INSERT INTO categories (id, name, description, created_at) VALUES ('4', 'سچرخ', 'ننن', '2025-08-19 22:06:53');
INSERT INTO categories (id, name, description, created_at) VALUES ('5', 'غلام سرور ', '123', '2025-08-20 10:48:30');

-- Table: products (5 records)
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('1', 'قاب زنحیر', '1', 'PRD-0001', '150.00', '220.00', '3', '5', 'ندارد', '2025-08-17 17:24:52');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('2', 'گیر بکس', '2', 'PRD-0002', '120.00', '160.00', '3', '5', 'درجه یک', '2025-08-18 12:18:03');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('3', 'کریم شاه', '2', '0001', '122.00', '132.00', '12', '5', '', '2025-08-18 13:28:33');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('4', 'یی', NULL, '0002', '212.00', '212.00', '12', '5', NULL, '2025-08-18 13:29:05');
INSERT INTO products (id, name, category_id, code, buy_price, sell_price, stock_quantity, min_stock, description, created_at) VALUES ('5', 'چراغ موتر لمر1', '1', '0003', '120.00', '150.00', '1', '5', 'ندارد', '2025-08-19 22:06:31');

-- Table: customers (3 records)
INSERT INTO customers (id, name, phone, address, created_at) VALUES ('1', 'علی احمد', '0728958423', 'Herat, Afghanistan', '2025-08-17 16:58:29');
INSERT INTO customers (id, name, phone, address, created_at) VALUES ('2', 'شاه میر', '0798999789', 'هرات چشت', '2025-08-18 12:22:36');
INSERT INTO customers (id, name, phone, address, created_at) VALUES ('3', 'نصیر احمد', '0798944187', 'هرات گذره', '2025-08-19 22:07:31');

