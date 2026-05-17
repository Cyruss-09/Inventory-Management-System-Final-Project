<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/index.php");
    exit();
}
include 'config/db.php';

$message = "";

// Handle Form Upgrades Save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $threshold = intval($_POST['low_stock_threshold']);
    $currency = trim($_POST['currency_symbol']);

    try {
        $stmt1 = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'low_stock_threshold'");
        $stmt1->execute([$threshold]);

        $stmt2 = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'currency_symbol'");
        $stmt2->execute([$currency]);

        $message = "<div style='color: #2ed573; margin-bottom: 15px; font-weight: 600;'>System configurations updated successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'>Database saving anomaly: " . $e->getMessage() . "</div>";
    }
}

// Extract properties current statuses safely from key-value database mapping
$settingsQuery = $conn->query("SELECT * FROM settings");
$settingsData = $settingsQuery->fetchAll(PDO::FETCH_KEY_PAIR);

// Fallbacks if data structure rows are absent
$currentThreshold = $settingsData['low_stock_threshold'] ?? 10;
$currentCurrency = $settingsData['currency_symbol'] ?? '₱';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; margin: 0;">
    <div class="container">
        <nav class="sidebar">
            <div class="logo-section"><div class="logo-circle"><img src="../assets/img/LOGO.png" alt="Logo" class="logo-img"></div></div>
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li class="active"><a href="settings.php"><i class="fas fa-sliders-h"></i> Settings</a></li>
            </ul>
            <div class="logout-section"><a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </nav>

        <main class="main-content" style="padding: 30px; box-sizing: border-box;">
            <div style="max-width: 600px; margin: 0 auto;">
                
                <div class="panel-card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                    <h4 style="margin: 0 0 10px 0; font-size: 20px; color: #2d3436;"><i class="fas fa-sliders" style="color: #ffb300; margin-right: 8px;"></i> Global System Preferences</h4>
                    <p style="color: #888; font-size: 13px; margin: 0 0 25px 0;">Configure warning parameters and localization rules used across data modules natively.</p>
                    
                    <?php echo $message; ?>
                    
                    <form method="POST" action="">
                        <div style="margin-bottom: 20px; border-bottom: 1px solid #f1f2f6; padding-bottom: 20px;">
                            <label style="display: block; font-size: 14px; color: #2d3436; margin-bottom: 6px; font-weight: 600;">Low Stock Warning Threshold</label>
                            <span style="color: #a0a5b5; font-size: 12px; display: block; margin-bottom: 10px;">Triggers an alert rule line item status inside your tables when stock dips below this limit value.</span>
                            <input type="number" name="low_stock_threshold" value="<?php echo htmlspecialchars($currentThreshold); ?>" required min="1" max="1000" style="width: 120px; padding: 10px; border: 1px solid #dcdde1; border-radius: 8px; box-sizing: border-box;">
                            <span style="font-size: 13px; color: #57606f; margin-left: 8px;">Units</span>
                        </div>

                        <div style="margin-bottom: 25px; padding-bottom: 10px;">
                            <label style="display: block; font-size: 14px; color: #2d3436; margin-bottom: 6px; font-weight: 600;">System Currency Representation Symbol</label>
                            <span style="color: #a0a5b5; font-size: 12px; display: block; margin-bottom: 10px;">Prefix used across financial evaluation grids and assets computation modules.</span>
                            <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($currentCurrency); ?>" required maxlength="5" style="width: 120px; padding: 10px; border: 1px solid #dcdde1; border-radius: 8px; box-sizing: border-box; text-align: center; font-weight: bold;">
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="submit" name="save_settings" style="background: #2d3436; color: #fff; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-save"></i> Save System Rules
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>
</body>
</html>