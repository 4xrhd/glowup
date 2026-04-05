<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'orders';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

$conn = getDbConnection();

switch ($type) {
    case 'orders':
        $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
        $filename = 'orders_export';
        break;
    case 'interests':
        $result = $conn->query("SELECT * FROM interest_submissions ORDER BY submitted_at DESC");
        $filename = 'interests_export';
        break;
    case 'transactions':
        $result = $conn->query("SELECT order_id, name, email, payment_method, transaction_id, price, order_status, created_at FROM orders ORDER BY created_at DESC");
        $filename = 'transactions_export';
        break;
    case 'products':
        $result = $conn->query("SELECT * FROM products ORDER BY sort_order ASC");
        $filename = 'products_export';
        break;
    default:
        $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
        $filename = 'export';
}

closeDbConnection($conn);

if ($format === 'json') {
    $data = [];
    while ($row = $result->fetch_assoc(MYSQLI_ASSOC)) { $data[] = $row; }
    header('Content-Type: application/json');
    header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
    echo json_encode($data, JSON_PRETTY_PRINT);
} else {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    echo "\xEF\xBB\xBF";
    $first = true;
    while ($row = $result->fetch_assoc(MYSQLI_ASSOC)) {
        if ($first) {
            echo implode(',', array_map(function($k) { return '"' . str_replace('"', '""', $k) . '"'; }, array_keys($row))) . "\n";
            $first = false;
        }
        echo implode(',', array_map(function($v) { return '"' . str_replace('"', '""', $v ?? '') . '"'; }, array_values($row))) . "\n";
    }
}
?>
