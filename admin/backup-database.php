<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$backup_date = date('Y-m-d_His');
$filename = "soundvision_backup_{$backup_date}.sql";

header('Content-Type: application/sql');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

$conn = getDbConnection();

$tables = ['orders', 'interest_submissions', 'admin_users', 'visitor_logs', 'products', 'payments'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW CREATE TABLE `{$table}`");
    if ($result && $row = $result->fetch_row()) {
        echo "--\n-- Table structure for `{$table}`\n--\n\n";
        echo $row[1] . ";\n\n";
    }

    $data = $conn->query("SELECT * FROM `{$table}`");
    if ($data && $data->num_rows > 0) {
        echo "--\n-- Data for `{$table}`\n--\n\n";
        while ($row = $data->fetch_assoc()) {
            $values = array_map(function($v) use ($conn) {
                if (is_null($v)) return 'NULL';
                return "'" . $conn->real_escape_string($v) . "'";
            }, array_values($row));
            echo "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
        }
        echo "\n";
    }
}

closeDbConnection($conn);
?>
