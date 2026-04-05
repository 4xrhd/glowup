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

    $model = isset($_GET['model']) ? $_GET['model'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $where = [];
    $params = [];
    $types = '';

    if ($model) { $where[] = 'model = ?'; $params[] = $model; $types .= 's'; }
    if ($search) { $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)'; $s = "%{$search}%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss'; }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) as total FROM interest_submissions {$whereClause}";
    $countStmt = $conn->prepare($countSql);
    if ($params) $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $sql = "SELECT * FROM interest_submissions {$whereClause} ORDER BY submitted_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $interests = [];
    while ($row = $result->fetch_assoc()) { $interests[] = $row; }
    $stmt->close();
    closeDbConnection($conn);

    echo json_encode(['success' => true, 'interests' => $interests, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $_DELETE);
    $id = isset($_DELETE['id']) ? intval($_DELETE['id']) : 0;
    if (!$id) { echo json_encode(['success' => false, 'message' => 'ID is required']); exit; }

    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM interest_submissions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    closeDbConnection($conn);
    echo json_encode(['success' => true, 'message' => 'Interest submission deleted successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
