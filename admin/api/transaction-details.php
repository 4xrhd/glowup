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

    $payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
    $has_transaction = isset($_GET['has_transaction']) ? $_GET['has_transaction'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $where = [];
    $params = [];
    $types = '';

    if ($payment_method) { $where[] = 'o.payment_method = ?'; $params[] = $payment_method; $types .= 's'; }
    if ($has_transaction === 'yes') { $where[] = 'o.transaction_id IS NOT NULL AND o.transaction_id != ""'; }
    if ($has_transaction === 'no') { $where[] = '(o.transaction_id IS NULL OR o.transaction_id = "")'; }
    if ($date_from) { $where[] = 'DATE(o.created_at) >= ?'; $params[] = $date_from; $types .= 's'; }
    if ($date_to) { $where[] = 'DATE(o.created_at) <= ?'; $params[] = $date_to; $types .= 's'; }
    if ($search) { $where[] = '(o.order_id LIKE ? OR o.name LIKE ? OR o.transaction_id LIKE ?)'; $s = "%{$search}%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss'; }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) as total FROM orders o {$whereClause}";
    $countStmt = $conn->prepare($countSql);
    if ($params) $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $sql = "SELECT o.order_id, o.name, o.email, o.payment_method, o.transaction_id, o.price, o.order_status, o.created_at FROM orders o {$whereClause} ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    while ($row = $result->fetch_assoc()) { $transactions[] = $row; }
    $stmt->close();

    $with_txn = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE transaction_id IS NOT NULL AND transaction_id != ''")->fetch_assoc()['cnt'];
    $without_txn = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE transaction_id IS NULL OR transaction_id = ''")->fetch_assoc()['cnt'];

    closeDbConnection($conn);

    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'total' => $total,
        'payment_stats' => [
            'with_transaction' => (int)$with_txn,
            'without_transaction' => (int)$without_txn
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $_PUT);
    $order_id = isset($_PUT['order_id']) ? $_PUT['order_id'] : '';
    $transaction_id = isset($_PUT['transaction_id']) ? htmlspecialchars($_PUT['transaction_id'], ENT_QUOTES, 'UTF-8') : '';

    if (!$order_id || !$transaction_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID and Transaction ID are required']);
        exit;
    }

    if (!preg_match('/^[A-Z0-9\-_]+$/i', $transaction_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID format']);
        exit;
    }

    $conn = getDbConnection();

    $check = $conn->prepare("SELECT id FROM orders WHERE transaction_id = ? AND order_id != ?");
    $check->bind_param("ss", $transaction_id, $order_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        closeDbConnection($conn);
        echo json_encode(['success' => false, 'message' => 'Transaction ID already exists for another order']);
        exit;
    }
    $check->close();

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

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
