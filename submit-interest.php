<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once 'config/database.php';

$model = isset($_POST['model']) ? htmlspecialchars($_POST['model'], ENT_QUOTES, 'UTF-8') : '';
$name = isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : '';
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '';
$phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : '';
$comments = isset($_POST['comments']) ? htmlspecialchars($_POST['comments'], ENT_QUOTES, 'UTF-8') : '';

if (!$model || !$name || !$email) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

$conn = getDbConnection();

$stmt = $conn->prepare("INSERT INTO interest_submissions (model, name, email, phone, comments) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $model, $name, $email, $phone, $comments);

if (!$stmt->execute()) {
    closeDbConnection($conn);
    echo json_encode(['success' => false, 'message' => 'Failed to submit interest form']);
    exit;
}

$stmt->close();

$subject = "Sound Vision Smart Glass - Interest Confirmed";
$headers = "From: noreply@soundvision.app\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$body = "
<html><body>
<h2>Interest Confirmed</h2>
<p>Dear {$name},</p>
<p>Thank you for your interest in our <strong>{$model}</strong> model!</p>
<p>We will contact you soon with more details.</p>
<p>Best regards,<br>GlowUp Beauty Team</p>
</body></html>
";

@mail($email, $subject, $body, $headers);

closeDbConnection($conn);

echo json_encode([
    'success' => true,
    'message' => 'Thank you for your interest! We will contact you soon.'
]);
?>
