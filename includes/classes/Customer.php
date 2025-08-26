<?php
require_once '../config/database.php';

class Customer {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAll($search = '', $limit = 50, $offset = 0) {
        $where = "WHERE status = 'active'";
        $params = [];
        
        if ($search) {
            $where .= " AND (name LIKE ? OR phone LIKE ? OR mobile LIKE ? OR code LIKE ?)";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        $query = "SELECT * FROM customers $where ORDER BY name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $query = "SELECT * FROM customers WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $query = "INSERT INTO customers (code, name, phone, mobile, email, address, 
                  city, postal_code, national_id, credit_limit) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['code'], $data['name'], $data['phone'], $data['mobile'],
            $data['email'], $data['address'], $data['city'], $data['postal_code'],
            $data['national_id'], $data['credit_limit']
        ]);
    }
    
    public function update($id, $data) {
        $query = "UPDATE customers SET name = ?, phone = ?, mobile = ?, email = ?, 
                  address = ?, city = ?, postal_code = ?, national_id = ?, credit_limit = ? 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'], $data['phone'], $data['mobile'], $data['email'],
            $data['address'], $data['city'], $data['postal_code'],
            $data['national_id'], $data['credit_limit'], $id
        ]);
    }
    
    public function delete($id) {
        $query = "UPDATE customers SET status = 'inactive' WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    public function updateBalance($id, $amount, $type = 'add') {
        if ($type === 'add') {
            $query = "UPDATE customers SET current_balance = current_balance + ? WHERE id = ?";
        } else {
            $query = "UPDATE customers SET current_balance = current_balance - ? WHERE id = ?";
        }
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$amount, $id]);
    }
    
    public function generateCode() {
        $query = "SELECT MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)) as max_code FROM customers WHERE code REGEXP '^C[0-9]+$'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $nextNumber = intval($result['max_code'] ?? 0) + 1;
        return 'C' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
?>