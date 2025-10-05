# 🏍 Motorcycle Parts Store Management System

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue?logo=php)](https://www.php.net/)  
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange?logo=mysql)](https://www.mysql.com/)  
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1.3-purple?logo=bootstrap)](https://getbootstrap.com/)  


A modular, role-based **Motorcycle Parts Store Management System** built with PHP (MVC pattern), MySQL, and a Bootstrap/JS frontend. Supports inventory, orders, suppliers, reports, and backups.

---

## 🚀 Features

- Secure login with **role-based access** (Admin, Sales Manager, Employee)  
- Central **dashboard** with key KPIs and metrics  
- **Product & category management** (CRUD, stock control)  
- **Customer & supplier management**  
- **Sales & purchase modules** (auto stock update)  
- **Invoice printing**  
- **Reporting** (sales, top products, inventory)  
- **User management** (Admins can manage users)  
- **Backup & restore**  
- **Security**: bcrypt password hashing, prepared statements, input validation, session control  

---

## 🛠 Tech Stack

| Layer     | Technology         |
|-----------|---------------------|
| Backend   | PHP 7.4+             |
| Database  | MySQL 5.7+            |
| Frontend  | HTML5, CSS3, JavaScript |
| UI Framework | Bootstrap 5.1.3       |
| Charts    | Chart.js              |
| Architecture | MVC Pattern         |

---

## 📁 Project Structure

motor/
├── config/
│ └── database.php
├── assets/
│ ├── css/
│ ├── js/
│ └── re-img/ # Real screenshots (dashboard, invoice etc.)
├── api/
├── includes/
├── backups/
├── *.php # core modules: dashboard, products, sales, etc.
└── database.sql

---

## 🧩 Installation

1. Clone or copy the project to `htdocs/motor`  
2. Import `database.sql` into MySQL  
3. Adjust DB credentials in `config/database.php`  
4. Visit `http://localhost/motor`

**Default Credentials**  


---

## 🧩 Installation

1. Clone or copy the project to `htdocs/motor`  
2. Import `database.sql` into MySQL  
3. Adjust DB credentials in `config/database.php`  
4. Visit `http://localhost/motor`

**Default Credentials**  

---

## 🔐 Security Notes

- Passwords hashed using **bcrypt**  
- Database interactions use **prepared statements**  
- Role-based access control  
- Server-side input validation  
- Secure session handling  

> **Tip:** Change default credentials immediately, restrict access to `config/`, and keep backups secure. Also به‌روزرسانی مداوم PHP و MySQL را فراموش نکنید.

---

## 📸 Screenshots

| Dashboard | Invoice | Reports |
|----------|---------|---------|
| ![Dashboard](assets/re-img/m%20(1).png) | ![Invoice](assets/re-img/m%20(2).png) | ![Reports](assets/re-img/m%20(3).png) | ![Reports](assets/re-img/m%20(4).png) | ![Users](assets/re-img/m%20(5).png) | ![Sales](assets/re-img/m%20(6).png) | 



---

## 🔮 Future Enhancements

- UI redesign with **TailwindCSS** & **Dark Mode**  
- **PDF export** for invoices  
- **RESTful API**  
- **Two-Factor Authentication (2FA)**, CSRF & XSS protection  
- **Notifications & SMS integration**  
- **Performance optimizations** (caching, lazy loading, query tuning)  

---

