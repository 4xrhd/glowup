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

    $total_visits = $conn->query("SELECT COUNT(*) as cnt FROM visitor_logs")->fetch_assoc()['cnt'];
    $unique_visitors = $conn->query("SELECT COUNT(DISTINCT ip_address) as cnt FROM visitor_logs")->fetch_assoc()['cnt'];
    $today_visits = $conn->query("SELECT COUNT(*) as cnt FROM visitor_logs WHERE DATE(visited_at) = CURDATE()")->fetch_assoc()['cnt'];

    $page_result = $conn->query("SELECT page, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors FROM visitor_logs GROUP BY page ORDER BY visits DESC");
    $visits_by_page = [];
    while ($row = $page_result->fetch_assoc()) { $visits_by_page[] = $row; }

    $daily_result = $conn->query("SELECT DATE(visited_at) as date, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors FROM visitor_logs WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(visited_at) ORDER BY date ASC");
    $daily_visits = [];
    while ($row = $daily_result->fetch_assoc()) { $daily_visits[] = $row; }

    closeDbConnection($conn);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_visits' => (int)$total_visits,
            'unique_visitors' => (int)$unique_visitors,
            'today_visits' => (int)$today_visits,
            'visits_by_page' => $visits_by_page,
            'daily_visits' => $daily_visits
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $conn = getDbConnection();
    $conn->query("TRUNCATE TABLE visitor_logs");
    closeDbConnection($conn);
    echo json_encode(['success' => true, 'message' => 'Visitor stats reset successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
