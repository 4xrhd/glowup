<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
$conn = getDbConnection();

$total_orders = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'];
$total_interests = $conn->query("SELECT COUNT(*) as cnt FROM interest_submissions")->fetch_assoc()['cnt'];

$status_result = $conn->query("SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status");
$orders_by_status = [];
while ($row = $status_result->fetch_assoc()) { $orders_by_status[$row['order_status']] = (int)$row['cnt']; }

$model_result = $conn->query("SELECT model, COUNT(*) as cnt FROM orders GROUP BY model");
$orders_by_model = [];
while ($row = $model_result->fetch_assoc()) { $orders_by_model[$row['model']] = (int)$row['cnt']; }

$interest_model_result = $conn->query("SELECT model, COUNT(*) as cnt FROM interest_submissions GROUP BY model");
$interests_by_model = [];
while ($row = $interest_model_result->fetch_assoc()) { $interests_by_model[$row['model']] = (int)$row['cnt']; }

$payment_result = $conn->query("SELECT payment_method, COUNT(*) as cnt FROM orders GROUP BY payment_method");
$orders_by_payment = [];
while ($row = $payment_result->fetch_assoc()) { $orders_by_payment[$row['payment_method']] = (int)$row['cnt']; }

$daily_result = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
$daily_orders = [];
while ($row = $daily_result->fetch_assoc()) { $daily_orders[] = $row; }

$with_txn = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE transaction_id IS NOT NULL AND transaction_id != ''")->fetch_assoc()['cnt'];
$without_txn = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE transaction_id IS NULL OR transaction_id = ''")->fetch_assoc()['cnt'];

$payment_txn = [];
foreach (['bkash', 'nagad', 'bank'] as $pm) {
    $total_pm = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE payment_method = '{$pm}'")->fetch_assoc()['cnt'];
    $with_pm = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE payment_method = '{$pm}' AND transaction_id IS NOT NULL AND transaction_id != ''")->fetch_assoc()['cnt'];
    $payment_txn[$pm] = [
        'total' => $total_pm,
        'with_transaction' => $with_pm,
        'without_transaction' => $total_pm - $with_pm
    ];
}

$recent_txn = $conn->query("SELECT order_id, payment_method, transaction_id, created_at, name FROM orders WHERE (transaction_id IS NULL OR transaction_id = '') ORDER BY created_at DESC LIMIT 10");
$recent_transactions = [];
while ($row = $recent_txn->fetch_assoc()) {
    $row['customer'] = $row['name'];
    unset($row['name']);
    $recent_transactions[] = $row;
}

closeDbConnection($conn);

echo json_encode([
    'success' => true,
    'stats' => [
        'total_orders' => (int)$total_orders,
        'total_interests' => (int)$total_interests,
        'recent_orders' => 5,
        'orders_by_status' => $orders_by_status,
        'orders_by_model' => $orders_by_model,
        'interests_by_model' => $interests_by_model,
        'orders_by_payment' => $orders_by_payment,
        'daily_orders' => $daily_orders,
        'transaction_stats' => [
            'with_transaction' => (int)$with_txn,
            'without_transaction' => (int)$without_txn
        ],
        'payment_with_transaction' => $payment_txn,
        'recent_transactions' => $recent_transactions
    ]
]);
?>
