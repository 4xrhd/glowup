<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$conn = getDbConnection();

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if ($slug) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if ($product) {
        if ($product['features']) {
            $product['features'] = json_decode($product['features'], true) ?: $product['features'];
        }
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} else {
    $status = isset($_GET['status']) ? $_GET['status'] : 'active';
    $sql = "SELECT id, name, slug, tagline, price, original_price, description, features, image_url, icon, badge, badge_color, gradient, status FROM products";
    $where = [];
    $params = [];
    $types = '';

    if ($status) {
        $where[] = 'status = ?';
        $params[] = $status;
        $types .= 's';
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY sort_order ASC';

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['features']) {
            $row['features'] = json_decode($row['features'], true) ?: $row['features'];
        }
        $products[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'products' => $products]);
}

closeDbConnection($conn);
?>
