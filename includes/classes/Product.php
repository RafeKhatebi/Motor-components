<?php
require_once '../config/database.php';

class Product {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAll($search = '', $category = '', $limit = 50, $offset = 0) {
        $where = "WHERE p.status = 'active'";
        $params = [];
        
        if ($search) {
            $where .= " AND (p.name LIKE ? OR p.code LIKE ? OR p.brand LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($category) {
            $where .= " AND p.category_id = ?";
            $params[] = $category;
        }
        
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  $where 
                  ORDER BY p.name 
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$id])) {
            return $stmt->fetch();
        }
        return false;
    }
    
    public function create($data) {
        $query = "INSERT INTO products (code, name, category_id, brand, model, description, 
                  purchase_price, sale_price, min_stock, current_stock, unit, barcode) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            $data['code'], $data['name'], $data['category_id'], $data['brand'],
            $data['model'], $data['description'], $data['purchase_price'],
            $data['sale_price'], $data['min_stock'], $data['current_stock'],
            $data['unit'], $data['barcode']
        ]);
        return $result && $stmt->rowCount() > 0;
    }
    
    public function update($id, $data) {
        $query = "UPDATE products SET name = ?, category_id = ?, brand = ?, model = ?, 
                  description = ?, purchase_price = ?, sale_price = ?, min_stock = ?, 
                  current_stock = ?, unit = ?, barcode = ? WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'], $data['category_id'], $data['brand'], $data['model'],
            $data['description'], $data['purchase_price'], $data['sale_price'],
            $data['min_stock'], $data['current_stock'], $data['unit'],
            $data['barcode'], $id
        ]);
    }
    
    public function delete($id) {
        $query = "UPDATE products SET status = 'inactive' WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    public function updateStock($id, $quantity, $type = 'set') {
        if ($type === 'add') {
            $query = "UPDATE products SET current_stock = current_stock + ? WHERE id = ?";
        } elseif ($type === 'subtract') {
            $query = "UPDATE products SET current_stock = current_stock - ? WHERE id = ?";
        } else {
            $query = "UPDATE products SET current_stock = ? WHERE id = ?";
        }
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$quantity, $id]);
    }
    
    public function getLowStock() {
        $query = "SELECT * FROM products WHERE current_stock <= min_stock AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>