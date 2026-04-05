<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once 'config/database.php';

$page = isset($_POST['page']) ? preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $_POST['page']) : 'unknown';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$conn = getDbConnection();
$stmt = $conn->prepare("INSERT INTO visitor_logs (page, ip_address, user_agent) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $page, $ip_address, $user_agent);
$stmt->execute();
$stmt->close();
closeDbConnection($conn);

echo json_encode(['success' => true]);
?>
