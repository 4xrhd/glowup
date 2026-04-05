<?php
header('Content-Type: application/json');
if (file_exists(__DIR__ . '/.setup-complete')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Database already initialized.']);
    exit;
}
require_once 'config/database.php';
$sqlFile = __DIR__ . '/database-setup.sql';
if (!file_exists($sqlFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'database-setup.sql not found.']);
    exit;
}
$sql = file_get_contents($sqlFile);
$statements = array_filter(array_map('trim', explode(';', $sql)));
$conn = getDbConnection();
$errors = [];
foreach ($statements as $i => $statement) {
    if (empty($statement) || $statement === '') continue;
    if (!$conn->query($statement)) {
        $errors[] = "Statement $i failed: " . $conn->error;
    }
}
if (empty($errors)) {
    file_put_contents(__DIR__ . '/.setup-complete', 'done');
    @unlink(__FILE__);
    closeDbConnection($conn);
    echo json_encode(['success' => true, 'message' => 'Database initialized successfully. Setup script self-deleted.']);
} else {
    closeDbConnection($conn);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Some statements failed:', 'errors' => $errors]);
}
?>
