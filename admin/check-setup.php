<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

echo "<!DOCTYPE html><html><head><title>Setup Check</title><style>body{font-family:sans-serif;padding:40px;background:#f1f5f9;}h1{color:#1e293b;}table{width:100%;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1);}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid #e2e8f0;}th{background:#f8fafc;}.pass{color:#22c55e;}.fail{color:#ef4444;}</style></head><body>";
echo "<h1>Setup Check</h1>";
echo "<a href='dashboard.php'>&larr; Back to Dashboard</a><br><br>";

$checks = [];

// DB connection
$conn = @new mysqli('localhost', 'root', 'toor', 'soundvision_db');
if ($conn->connect_error) {
    $checks[] = ['Check' => 'Database Connection', 'Status' => 'FAIL', 'Detail' => $conn->connect_error];
} else {
    $checks[] = ['Check' => 'Database Connection', 'Status' => 'PASS', 'Detail' => 'Connected to soundvision_db'];

    $tables = ['orders', 'interest_submissions', 'admin_users', 'visitor_logs', 'products', 'payments'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($result->num_rows > 0) {
            $count = $conn->query("SELECT COUNT(*) as cnt FROM `{$table}`")->fetch_assoc()['cnt'];
            $checks[] = ['Check' => "Table: {$table}", 'Status' => 'PASS', 'Detail' => "{$count} rows"];
        } else {
            $checks[] = ['Check' => "Table: {$table}", 'Status' => 'FAIL', 'Detail' => 'Table does not exist'];
        }
    }

    $adminCount = $conn->query("SELECT COUNT(*) as cnt FROM admin_users")->fetch_assoc()['cnt'];
    $checks[] = ['Check' => 'Admin Users', 'Status' => $adminCount > 0 ? 'PASS' : 'FAIL', 'Detail' => "{$adminCount} admin user(s)"];
}

echo "<table><tr><th>Check</th><th>Status</th><th>Detail</th></tr>";
foreach ($checks as $c) {
    $cls = $c['Status'] === 'PASS' ? 'pass' : 'fail';
    echo "<tr><td>{$c['Check']}</td><td class='{$cls}'>{$c['Status']}</td><td>{$c['Detail']}</td></tr>";
}
echo "</table></body></html>";
?>
