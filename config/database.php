<?php
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: 'toor');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'soundvision_db');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');

function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function closeDbConnection($conn) {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
?>
