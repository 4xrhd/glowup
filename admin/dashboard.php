<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — GlowUp Beauty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-spa"></i> GlowUp Admin</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" data-page="dashboard"><i class="fas fa-chart-bar"></i> Dashboard</a>
            <a href="#" class="nav-item" data-page="orders"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="#" class="nav-item" data-page="transactions"><i class="fas fa-credit-card"></i> Transactions</a>
            <a href="#" class="nav-item" data-page="interests"><i class="fas fa-heart"></i> Interests</a>
            <a href="#" class="nav-item" data-page="products"><i class="fas fa-box"></i> Products</a>
            <a href="#" class="nav-item" data-page="visitors"><i class="fas fa-users"></i> Visitors</a>
            <a href="#" class="nav-item" data-page="settings"><i class="fas fa-cog"></i> Settings</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-item logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <h1 id="page-title">Dashboard</h1>
            <div class="header-actions">
                <span id="admin-username" class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </header>

        <div id="page-content" class="page-content">
        </div>
    </main>

    <div id="modal-overlay" class="modal-overlay hidden">
        <div id="modal-content" class="modal-content">
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadPage('dashboard');
        });
    </script>
</body>
</html>
