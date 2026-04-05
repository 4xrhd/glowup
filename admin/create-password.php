<?php
$password = isset($_GET['password']) ? $_GET['password'] : '';
if ($password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "<!DOCTYPE html><html><head><title>Password Hash Generator</title><style>body{font-family:sans-serif;padding:40px;background:#f1f5f9;}code{background:#1e293b;color:#22c55e;padding:8px 16px;border-radius:6px;display:inline-block;word-break:break-all;}</style></head><body>";
    echo "<h1>Password Hash</h1>";
    echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
    echo "<p><strong>Hash:</strong></p>";
    echo "<code>{$hash}</code>";
    echo "<p style='margin-top:20px;'><a href='check-setup.php'>&larr; Back to Setup Check</a></p>";
    echo "</body></html>";
} else {
    echo "<!DOCTYPE html><html><head><title>Password Hash Generator</title><style>body{font-family:sans-serif;padding:40px;background:#f1f5f9;}input{padding:10px;width:300px;border:1px solid #e2e8f0;border-radius:6px;}button{padding:10px 20px;background:#06b6d4;color:#fff;border:none;border-radius:6px;cursor:pointer;}</style></head><body>";
    echo "<h1>Password Hash Generator</h1>";
    echo "<form><input type='text' name='password' placeholder='Enter password to hash' required><button type='submit'>Generate Hash</button></form>";
    echo "<p style='margin-top:20px;'><a href='check-setup.php'>&larr; Back to Setup Check</a></p>";
    echo "</body></html>";
}
?>
