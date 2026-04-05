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
    $result = $conn->query("SELECT setting_key, setting_value, setting_group FROM site_settings ORDER BY setting_group, setting_key");
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[] = $row;
    }
    closeDbConnection($conn);
    echo json_encode(['success' => true, 'settings' => $settings]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = isset($_POST['key']) ? $_POST['key'] : '';
    $value = isset($_POST['value']) ? $_POST['value'] : '';

    if (!$key) {
        echo json_encode(['success' => false, 'message' => 'Setting key is required']);
        exit;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
    $stmt->close();
    closeDbConnection($conn);

    echo json_encode(['success' => true, 'message' => 'Setting updated successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
