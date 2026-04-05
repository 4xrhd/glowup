<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = getDbConnection();
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $where = [];
    $params = [];
    $types = '';

    if ($status) { $where[] = 'status = ?'; $params[] = $status; $types .= 's'; }
    if ($search) { $where[] = '(name LIKE ? OR slug LIKE ?)'; $s = "%{$search}%"; $params[] = $s; $params[] = $s; $types .= 'ss'; }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT * FROM products {$whereClause} ORDER BY sort_order ASC";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
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
    closeDbConnection($conn);

    echo json_encode(['success' => true, 'products' => $products, 'total' => count($products)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'update_status') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        if (!$id || !$status) { echo json_encode(['success' => false, 'message' => 'ID and status required']); exit; }
        $conn = getDbConnection();
        $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
        closeDbConnection($conn);
        echo json_encode(['success' => true, 'message' => 'Product status updated']);
        exit;
    }

    if ($action === 'update_sort') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID required']); exit; }
        $conn = getDbConnection();
        $stmt = $conn->prepare("UPDATE products SET sort_order = ? WHERE id = ?");
        $stmt->bind_param("ii", $sort_order, $id);
        $stmt->execute();
        $stmt->close();
        closeDbConnection($conn);
        echo json_encode(['success' => true, 'message' => 'Sort order updated']);
        exit;
    }

    $name = isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : '';
    $slug = isset($_POST['slug']) ? htmlspecialchars($_POST['slug'], ENT_QUOTES, 'UTF-8') : '';
    $tagline = isset($_POST['tagline']) ? htmlspecialchars($_POST['tagline'], ENT_QUOTES, 'UTF-8') : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $original_price = isset($_POST['original_price']) && $_POST['original_price'] !== '' ? floatval($_POST['original_price']) : null;
    $description = isset($_POST['description']) ? htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8') : '';
    $features_raw = isset($_POST['features']) ? $_POST['features'] : '[]';
    $features = is_string($features_raw) ? $features_raw : json_encode($features_raw);
    $image_url = isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url'], ENT_QUOTES, 'UTF-8') : '';
    $icon = isset($_POST['icon']) ? htmlspecialchars($_POST['icon'], ENT_QUOTES, 'UTF-8') : 'fa-spa';
    $badge = isset($_POST['badge']) ? htmlspecialchars($_POST['badge'], ENT_QUOTES, 'UTF-8') : '';
    $badge_color = isset($_POST['badge_color']) ? htmlspecialchars($_POST['badge_color'], ENT_QUOTES, 'UTF-8') : 'bg-rose-100 text-rose-600';
    $gradient = isset($_POST['gradient']) ? htmlspecialchars($_POST['gradient'], ENT_QUOTES, 'UTF-8') : 'from-rose-50 to-pink-50';
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;

    if (!$name || !$slug || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Name, slug, and price are required']);
        exit;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO products (name, slug, tagline, price, original_price, description, features, image_url, icon, badge, badge_color, gradient, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssddsssssss si", $name, $slug, $tagline, $price, $original_price, $description, $features, $image_url, $icon, $badge, $badge_color, $gradient, $status, $sort_order);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();
    closeDbConnection($conn);

    echo json_encode(['success' => true, 'message' => 'Product created successfully', 'id' => $id]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $_PUT);

    $id = isset($_PUT['id']) ? intval($_PUT['id']) : 0;
    $name = isset($_PUT['name']) ? htmlspecialchars($_PUT['name'], ENT_QUOTES, 'UTF-8') : '';
    $slug = isset($_PUT['slug']) ? htmlspecialchars($_PUT['slug'], ENT_QUOTES, 'UTF-8') : '';
    $tagline = isset($_PUT['tagline']) ? htmlspecialchars($_PUT['tagline'], ENT_QUOTES, 'UTF-8') : '';
    $price = isset($_PUT['price']) ? floatval($_PUT['price']) : 0;
    $original_price = isset($_PUT['original_price']) && $_PUT['original_price'] !== '' ? floatval($_PUT['original_price']) : null;
    $description = isset($_PUT['description']) ? htmlspecialchars($_PUT['description'], ENT_QUOTES, 'UTF-8') : '';
    $features_raw = isset($_PUT['features']) ? $_PUT['features'] : '[]';
    $features = is_string($features_raw) ? $features_raw : json_encode($features_raw);
    $image_url = isset($_PUT['image_url']) ? htmlspecialchars($_PUT['image_url'], ENT_QUOTES, 'UTF-8') : '';
    $icon = isset($_PUT['icon']) ? htmlspecialchars($_PUT['icon'], ENT_QUOTES, 'UTF-8') : 'fa-spa';
    $badge = isset($_PUT['badge']) ? htmlspecialchars($_PUT['badge'], ENT_QUOTES, 'UTF-8') : '';
    $badge_color = isset($_PUT['badge_color']) ? htmlspecialchars($_PUT['badge_color'], ENT_QUOTES, 'UTF-8') : 'bg-rose-100 text-rose-600';
    $gradient = isset($_PUT['gradient']) ? htmlspecialchars($_PUT['gradient'], ENT_QUOTES, 'UTF-8') : 'from-rose-50 to-pink-50';
    $status = isset($_PUT['status']) ? $_PUT['status'] : 'active';
    $sort_order = isset($_PUT['sort_order']) ? intval($_PUT['sort_order']) : 0;

    if (!$id || !$name || !$slug) {
        echo json_encode(['success' => false, 'message' => 'ID, name, and slug are required']);
        exit;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE products SET name=?, slug=?, tagline=?, price=?, original_price=?, description=?, features=?, image_url=?, icon=?, badge=?, badge_color=?, gradient=?, status=?, sort_order=? WHERE id=?");
    $stmt->bind_param("sssddssssssssii", $name, $slug, $tagline, $price, $original_price, $description, $features, $image_url, $icon, $badge, $badge_color, $gradient, $status, $sort_order, $id);
    $stmt->execute();
    $stmt->close();
    closeDbConnection($conn);

    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $_DELETE);
    $id = isset($_DELETE['id']) ? intval($_DELETE['id']) : 0;
    if (!$id) { echo json_encode(['success' => false, 'message' => 'ID is required']); exit; }

    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    closeDbConnection($conn);

    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
