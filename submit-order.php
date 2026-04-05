<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once 'config/database.php';

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$model = isset($_POST['model']) ? htmlspecialchars($_POST['model'], ENT_QUOTES, 'UTF-8') : '';
$price = isset($_POST['price']) ? htmlspecialchars($_POST['price'], ENT_QUOTES, 'UTF-8') : '';
$name = isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : '';
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '';
$phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : '';
$address = isset($_POST['address']) ? htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8') : '';
$payment_method = isset($_POST['payment_method']) ? htmlspecialchars($_POST['payment_method'], ENT_QUOTES, 'UTF-8') : '';
$transaction_id_input = isset($_POST['transaction_id']) ? htmlspecialchars($_POST['transaction_id'], ENT_QUOTES, 'UTF-8') : '';

if (!$model || !$price || !$name || !$email || !$phone || !$address || !$payment_method) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

$phone_clean = preg_replace('/[\s-]/', '', $phone);
if (!preg_match('/^(?:\+88)?01\d{9}$/', $phone_clean)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Bangladeshi phone number']);
    exit;
}

$orderId = 'GU-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

$final_transaction_id = '';
if ($payment_method === 'bkash') {
    $final_transaction_id = $transaction_id_input;
} elseif ($payment_method === 'nagad') {
    $final_transaction_id = isset($_POST['nagad_transaction_id']) ? htmlspecialchars($_POST['nagad_transaction_id'], ENT_QUOTES, 'UTF-8') : '';
} elseif ($payment_method === 'bank') {
    $final_transaction_id = isset($_POST['bank_transaction_id']) ? htmlspecialchars($_POST['bank_transaction_id'], ENT_QUOTES, 'UTF-8') : '';
}

$conn = getDbConnection();

$stmt = $conn->prepare("INSERT INTO orders (order_id, product_id, model, price, name, email, phone, address, payment_method, transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sissssssss", $orderId, $product_id, $model, $price, $name, $email, $phone_clean, $address, $payment_method, $final_transaction_id);

if (!$stmt->execute()) {
    closeDbConnection($conn);
    echo json_encode(['success' => false, 'message' => 'Failed to place order. Please try again.']);
    exit;
}
$stmt->close();

$siteName = 'GlowUp Beauty';
$contactEmail = 'info@glowup.bd';
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('site_name','email')");
if ($settingsResult) {
    while ($row = $settingsResult->fetch_assoc()) {
        if ($row['setting_key'] === 'site_name') $siteName = $row['setting_value'];
        if ($row['setting_key'] === 'email') $contactEmail = $row['setting_value'];
    }
}

$customerSubject = "{$siteName} - Order Confirmation #{$orderId}";
$customerHeaders = "From: orders@glowup.bd\r\nContent-Type: text/html; charset=UTF-8\r\n";

$bankDetails = '';
if ($payment_method === 'bank') {
    $bankDetails = '<p><strong>Bank Transfer Details:</strong></p><ul><li>Bank: City Bank</li><li>Account Name: ISHRAQ UDDIN CHOWDHURY</li><li>Account Number: 2103833949001</li><li>Routing Number: 225261732</li><li>Reference: ' . htmlspecialchars($orderId) . '</li></ul>';
}

$customerBody = "<html><body><h2>Order Confirmation</h2><p>Dear {$name},</p><p>Thank you for your order from {$siteName}!</p><table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;'><tr><td><strong>Order ID</strong></td><td>{$orderId}</td></tr><tr><td><strong>Product</strong></td><td>{$model}</td></tr><tr><td><strong>Price</strong></td><td>{$price}</td></tr><tr><td><strong>Payment Method</strong></td><td>{$payment_method}</td></tr><tr><td><strong>Transaction ID</strong></td><td>{$final_transaction_id}</td></tr></table>{$bankDetails}<p><strong>Delivery Timeline:</strong> 2-5 business days after payment verification.</p><p>Contact us at {$contactEmail} for any queries.</p></body></html>";

@mail($email, $customerSubject, $customerBody, $customerHeaders);

$adminSubject = "New Order #{$orderId} - {$siteName}";
$adminHeaders = "From: orders@glowup.bd\r\nContent-Type: text/html; charset=UTF-8\r\n";
$adminBody = "<html><body><h2>New Order Received</h2><table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;'><tr><td><strong>Order ID</strong></td><td>{$orderId}</td></tr><tr><td><strong>Customer</strong></td><td>{$name} ({$email})</td></tr><tr><td><strong>Phone</strong></td><td>{$phone_clean}</td></tr><tr><td><strong>Address</strong></td><td>{$address}</td></tr><tr><td><strong>Product</strong></td><td>{$model}</td></tr><tr><td><strong>Price</strong></td><td>{$price}</td></tr><tr><td><strong>Payment Method</strong></td><td>{$payment_method}</td></tr><tr><td><strong>Transaction ID</strong></td><td>{$final_transaction_id}</td></tr></table><p><strong>ACTION REQUIRED:</strong> Verify payment and update order status.</p></body></html>";

@mail('admin@glowup.bd', $adminSubject, $adminBody, $adminHeaders);

closeDbConnection($conn);

echo json_encode(['success' => true, 'message' => 'Order placed successfully!', 'order_id' => $orderId]);
?>
