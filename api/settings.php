<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$conn = getDbConnection();

$group = isset($_GET['group']) ? $_GET['group'] : '';

if ($group) {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_group = ?");
    $stmt->bind_param("s", $group);
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
    echo json_encode(['success' => true, 'settings' => $settings]);
} else {
    $result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode(['success' => true, 'settings' => $settings]);
}

closeDbConnection($conn);
?>
