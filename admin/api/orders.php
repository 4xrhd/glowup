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
    $payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
    $has_transaction = isset($_GET['has_transaction']) ? $_GET['has_transaction'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $where = [];
    $params = [];
    $types = '';

    if ($status) { $where[] = 'order_status = ?'; $params[] = $status; $types .= 's'; }
    if ($payment_method) { $where[] = 'payment_method = ?'; $params[] = $payment_method; $types .= 's'; }
    if ($has_transaction === 'yes') { $where[] = 'transaction_id IS NOT NULL AND transaction_id != ""'; }
    if ($has_transaction === 'no') { $where[] = '(transaction_id IS NULL OR transaction_id = "")'; }
    if ($search) { $where[] = '(order_id LIKE ? OR name LIKE ? OR email LIKE ?)'; $s = "%{$search}%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss'; }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) as total FROM orders {$whereClause}";
    $countStmt = $conn->prepare($countSql);
    if ($params) $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $sql = "SELECT * FROM orders {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) { $orders[] = $row; }
    $stmt->close();
    closeDbConnection($conn);

    echo json_encode(['success' => true, 'orders' => $orders, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';

    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        exit;
    }

    $conn = getDbConnection();

    if ($action === 'update_status') {
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        if (!$status) { echo json_encode(['success' => false, 'message' => 'Status is required']); exit; }
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("ss", $status, $order_id);
        $stmt->execute();
        $stmt->close();
        $stmt2 = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt2->bind_param("s", $order_id);
        $stmt2->execute();
        $order = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        closeDbConnection($conn);
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully', 'order' => $order]);
        exit;
    }

    if ($action === 'update_transaction') {
        $transaction_id = isset($_POST['transaction_id']) ? htmlspecialchars($_POST['transaction_id'], ENT_QUOTES, 'UTF-8') : '';
        if (!$transaction_id) { echo json_encode(['success' => false, 'message' => 'Transaction ID is required']); exit; }
        $stmt = $conn->prepare("UPDATE orders SET transaction_id = ? WHERE order_id = ?");
        $stmt->bind_param("ss", $transaction_id, $order_id);
        $stmt->execute();
        $stmt->close();
        $stmt2 = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt2->bind_param("s", $order_id);
        $stmt2->execute();
        $order = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        closeDbConnection($conn);
        echo json_encode(['success' => true, 'message' => 'Transaction ID updated successfully', 'order' => $order]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $_DELETE);
    $order_id = isset($_DELETE['order_id']) ? $_DELETE['order_id'] : '';
    if (!$order_id) { echo json_encode(['success' => false, 'message' => 'Order ID is required']); exit; }

    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $stmt->close();
    closeDbConnection($conn);
    echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
