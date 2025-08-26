<?php
require_once '../../includes/auth.php';
requireLogin();

$db = new Database();
$conn = $db->connect();

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.barcode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

$sql .= " ORDER BY p.name";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>مدیریت محصولات</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <nav class="nav">
                <div><h2>مدیریت محصولات</h2></div>
                <ul>
                    <li><a href="../../dashboard.php">داشبورد</a></li>
                    <li><a href="add.php">افزودن محصول</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <form method="GET" style="display: flex; gap: 15px; align-items: end;">
                <div class="form-group" style="flex: 1;">
                    <label>جستجو:</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="نام محصول یا بارکد">
                </div>
                
                <div class="form-group">
                    <label>دسته‌بندی:</label>
                    <select name="category" class="form-control">
                        <option value="">همه دسته‌ها</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">جستجو</button>
                <a href="index.php" class="btn btn-warning">پاک کردن</a>
            </form>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام محصول</th>
                        <th>دسته‌بندی</th>
                        <th>بارکد</th>
                        <th>قیمت خرید</th>
                        <th>قیمت فروش</th>
                        <th>موجودی</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td><?= htmlspecialchars($product['barcode']) ?></td>
                            <td><?= number_format($product['buy_price']) ?> تومان</td>
                            <td><?= number_format($product['sell_price']) ?> تومان</td>
                            <td><?= $product['stock_quantity'] ?></td>
                            <td>
                                <?php if ($product['stock_quantity'] <= $product['min_stock']): ?>
                                    <span style="color: red;">کم موجود</span>
                                <?php else: ?>
                                    <span style="color: green;">موجود</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-warning">ویرایش</a>
                                <a href="delete.php?id=<?= $product['id'] ?>" class="btn btn-danger" 
                                   onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>