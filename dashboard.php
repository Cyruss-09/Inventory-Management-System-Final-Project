<?php
session_start();

// If the session variable 'user_id' is NOT set, the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Send them back to the login page immediately
    header("Location: auth/login.php");
    exit();
}
?>
<!-- Your Dashboard HTML starts here -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <!-- Using FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo-section">
                <div class="logo-circle">
                    <!-- Replace with your actual logo image -->
                    <img src="../assets/img/LOGO.png" alt="Cyruss Logo" class="logo-img">
                </div>
            </div>

            <ul class="nav-links">
                <li class="active"><a href="#"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
            </ul>

            <div class="logout-section">
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <span>Total Products</span>
                        <h3>0</h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-cubes"></i></div>
                    <div class="stat-info">
                        <span>Total Stocks</span>
                        <h3>0</h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red-icon"><i class="fas fa-arrow-trend-down"></i></div>
                    <div class="stat-info">
                        <span>Low Stocks Items</span>
                        <h3>0</h3>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="stat-info">
                        <span>Recently Added Products</span>
                        <h3>0</h3>
                    </div>
                </div>
            </header>
            <hr class="divider">
        </main>
    </div>

</body>
</html>