<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'authenticated' => isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true,
        'user' => [
            'username' => $_SESSION['admin_username'] ?? null,
            'role' => $_SESSION['admin_role'] ?? null
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!$username || !$password) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, username, password_hash, email, role FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    closeDbConnection($conn);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];

        $conn2 = getDbConnection();
        $stmt2 = $conn2->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $stmt2->bind_param("i", $user['id']);
        $stmt2->execute();
        $stmt2->close();
        closeDbConnection($conn2);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
