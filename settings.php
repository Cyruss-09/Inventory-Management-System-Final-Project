<?php
// 1. Force the timezone to match your local runtime environment (Philippine Standard Time)
date_default_timezone_set('Asia/Manila');

session_start();

// Redirect to login if user session doesn't exist
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php"); 
    exit(); 
}

// Replace this with your actual database connection file path
include 'config/db.php'; 

// Check if the currently logged-in user is an Admin
$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';

// AJAX Endpoint: Process saving configurations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_settings') {
    header('Content-Type: application/json');
    
    if (!$isAdmin) {
        echo json_encode(['status' => 'error', 'message' => 'Access Denied: Administrative privileges required.']);
        exit();
    }

    $low_stock = isset($_POST['low_stock_threshold']) ? (int)$_POST['low_stock_threshold'] : 20;

    try {
        // Check if a settings row with ID 1 already exists
        $checkRow = $conn->prepare("SELECT COUNT(*) FROM settings WHERE id = 1");
        $checkRow->execute();
        $exists = $checkRow->fetchColumn();

        if ($exists) {
            // Row exists, perform an UPDATE query
            $stmt = $conn->prepare("UPDATE settings SET low_stock_threshold = ? WHERE id = 1");
            $stmt->execute([$low_stock]);
        } else {
            // Table is empty, initialize it with an INSERT query
            $stmt = $conn->prepare("INSERT INTO settings (id, low_stock_threshold) VALUES (1, ?)");
            $stmt->execute([$low_stock]);
        }

        // --- AUTOMATED INITIALIZATION ENGINE ---
        // Ensure the table exists dynamically and record the save preference event
        $conn->exec("CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL,
            action TEXT NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $currentUser = $_SESSION['username'] ?? 'Admin';
        $logStmt = $conn->prepare("INSERT INTO activity_log (username, action, timestamp) VALUES (?, ?, ?)");
        
        // Explicitly pass the calculated timezone date instead of relying on default SQL server time
        $logStmt->execute([$currentUser, "Modified global low-stock warning threshold configuration to ($low_stock units).", date('Y-m-d H:i:s')]);
        // --------------------------------------

        echo json_encode(['status' => 'success', 'message' => 'System configuration parameters updated successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit();
}

// Default system configurations loading fallbacks
$low_stock_current = 20; 
$currency_current = '₱'; 

try {
    $getSettings = $conn->query("SELECT * FROM settings LIMIT 1");
    if ($settingRow = $getSettings->fetch(PDO::FETCH_ASSOC)) {
        $low_stock_current = $settingRow['low_stock_threshold'] ?? 20;
    }
} catch (Exception $e) {}

// Arrays to populate live report sub-components safely
$lowStockItems = [];
$categoryReports = [];
$auditLogs = [];

try {
    // 1. Fetch products falling below the configured threshold parameters
    $lowStockQuery = $conn->prepare("SELECT product_name, quantity, price FROM products WHERE quantity <= ? ORDER BY quantity ASC LIMIT 5");
    $lowStockQuery->execute([$low_stock_current]);
    $lowStockItems = $lowStockQuery->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch category metrics for the breakdown analytics mapping
    $categoryQuery = $conn->query("SELECT category, COUNT(*) as total_skus, SUM(quantity) as stock_volume, SUM(quantity * price) as category_value FROM products GROUP BY category ORDER BY category_value DESC");
    $categoryReports = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

    // 3. Activity / Audit Trail log check engine (Pulls LIVE database entries)
    $checkLogTable = $conn->query("SHOW TABLES LIKE 'activity_log'")->fetchAll();
    if (count($checkLogTable) > 0) {
        $auditLogs = $conn->query("SELECT username, action, timestamp FROM activity_log ORDER BY timestamp DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback default mock-logs if table isn't built yet
        $auditLogs = [
            ['username' => 'Admin', 'action' => 'Modified system low-stock warning metrics', 'timestamp' => date('Y-m-d H:i', strtotime('-10 mins'))],
            ['username' => $_SESSION['username'] ?? 'Cyrus', 'action' => 'Accessed configuration settings control panel', 'timestamp' => date('Y-m-d H:i', strtotime('-1 min'))]
        ];
    }
} catch (Exception $e) {
    // Graceful error isolation protection blocks
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global System Preferences</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; min-height: 100vh;">
    
    <div id="toastNotification" style="display: none; position: fixed; top: 30px; right: 30px; z-index: 9999; padding: 16px 24px; border-radius: 10px; color: white; font-weight: 600; font-size: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); align-items: center; gap: 10px; transform: translateY(-20px); opacity: 0; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        <i id="toastIcon" class="fas"></i>
        <span id="toastMessage"></span>
    </div>

    <div class="container" style="display: flex; min-height: 100vh; width: 100%;">
        
        <nav class="sidebar" style="width: 260px; flex-shrink: 0;">
            <div class="logo-section">
                <div class="logo-circle">
                    <img src="../assets/img/LOGO.png" alt="Cyruss Logo" class="logo-img">
                </div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="users_management.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li class="active"><a href="settings.php"><i class="fas fa-sliders-h"></i> Settings</a></li>
            </ul>
            <div class="logout-section">
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <main class="main-content" style="flex: 1; display: flex; justify-content: center; align-items: flex-start; padding: 40px; box-sizing: border-box;">
            
            <div style="width: 100%; max-width: 950px; display: flex; flex-direction: column; gap: 25px;">
                
                <div class="panel-card" style="background: #fff; padding: 0; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #edf2f7; display: flex; min-height: 540px; overflow: hidden;">
                    
                    <div style="width: 230px; background: #f8fafc; border-right: 1px solid #edf2f7; padding: 30px 15px; display: flex; flex-direction: column; gap: 8px;">
                        <button type="button" class="tab-link active" onclick="switchSettingsTab(event, 'inventory-tab')" style="width: 100%; border: none; padding: 12px 16px; border-radius: 8px; text-align: left; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.2s;">
                            <i class="fas fa-boxes"></i> Inventory Rules
                        </button>
                        <button type="button" class="tab-link" onclick="switchSettingsTab(event, 'audit-tab')" style="width: 100%; border: none; padding: 12px 16px; border-radius: 8px; text-align: left; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.2s;">
                            <i class="fas fa-history"></i> Activity & Security Log
                        </button>
                        <button type="button" class="tab-link" onclick="switchSettingsTab(event, 'reports-tab')" style="width: 100%; border: none; padding: 12px 16px; border-radius: 8px; text-align: left; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.2s;">
                            <i class="fas fa-chart-line"></i> Stock Analytics
                        </button>
                    </div>

                    <form id="settingsForm" style="flex: 1; padding: 35px; display: flex; flex-direction: column; justify-content: space-between; margin:0;">
                        
                        <div>
                            <div id="inventory-tab" class="tab-content" style="display: block;">
                                <h3 style="margin: 0 0 4px 0; color: #1a202c; font-size: 20px; font-weight: 700;">Inventory Control Metrics</h3>
                                <p style="margin: 0 0 25px 0; color: #718096; font-size: 13px;">Configure thresholds and criteria for stock tracking nodes.</p>
                                
                                <div style="display: flex; flex-direction: column; gap: 20px;">
                                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center; gap: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.01);">
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                                <i class="fas fa-cubes" style="color: #4a5568; font-size: 16px;"></i>
                                                <label style="font-weight: 600; color: #2d3748; font-size: 14px;">Low Stock Threshold Warning</label>
                                            </div>
                                            <span style="display: block; color: #718096; font-size: 12px; line-height: 1.5; max-width: 460px;">
                                                Automatically highlights product configurations on dashboard interfaces when stock values slip beneath this targeted fallback limit.
                                            </span>
                                        </div>
                                        <div style="position: relative; display: flex; align-items: center; width: 140px; flex-shrink: 0;">
                                            <input type="number" name="low_stock_threshold" value="<?php echo htmlspecialchars($low_stock_current); ?>" min="0" max="1000" <?php echo !$isAdmin ? 'disabled' : ''; ?> style="width: 100%; padding: 10px 48px 10px 14px; border: 2px solid #edf2f7; background-color: #f8fafc; border-radius: 8px; font-size: 14px; font-weight: 700; color: #1a202c; outline: none; transition: border-color 0.2s;">
                                            <span style="position: absolute; right: 14px; color: #718096; font-size: 12px; font-weight: 600; pointer-events: none;">Units</span>
                                        </div>
                                    </div>

                                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px 20px; display: flex; gap: 12px; align-items: flex-start;">
                                        <i class="fas fa-shield-alt" style="color: #16a34a; font-size: 16px; margin-top: 2px;"></i>
                                        <div>
                                            <h4 style="margin: 0 0 4px 0; color: #14532d; font-size: 13px; font-weight: 700;">Global Parameters Protected</h4>
                                            <p style="margin: 0; color: #166534; font-size: 12px; line-height: 1.5;">
                                                Altering these tracking thresholds dynamically recalculates active asset alerts across the Stock Analytics layout and the main product management catalog profiles.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="audit-tab" class="tab-content" style="display: none;">
                                <h3 style="margin: 0 0 4px 0; color: #1a202c; font-size: 20px; font-weight: 700;">System Activity & Audit Trail</h3>
                                <p style="margin: 0 0 20px 0; color: #718096; font-size: 13px;">Review the most recent administrative adjustments and security records.</p>
                                
                                <div style="display: flex; flex-direction: column; gap: 12px; max-height: 380px; overflow-y: auto; padding-right: 5px;">
                                    <?php if(empty($auditLogs)): ?>
                                        <div style="text-align: center; color: #a0aec0; padding: 25px; font-weight: 600; font-size: 13px; background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 8px;">No systemic activity items tracked inside database engine modules yet.</div>
                                    <?php else: ?>
                                        <?php foreach ($auditLogs as $log): ?>
                                            <div style="background: #f8fafc; border: 1px solid #edf2f7; padding: 12px 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <div style="background: #e2e8f0; color: #4a5568; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">
                                                        <i class="fas fa-user-shield"></i>
                                                    </div>
                                                    <div>
                                                        <span style="display: block; font-size: 13px; font-weight: 700; color: #2d3748;"><?php echo htmlspecialchars($log['username']); ?></span>
                                                        <span style="display: block; font-size: 12px; color: #4a5568; line-height: 1.3; margin-top: 2px;"><?php echo htmlspecialchars($log['action']); ?></span>
                                                    </div>
                                                </div>
                                                <span style="font-size: 11px; font-weight: 600; color: #a0aec0; white-space: nowrap;"><i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($log['timestamp'])); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div id="reports-tab" class="tab-content" style="display: none;">
                                <h3 style="margin: 0 0 4px 0; color: #1a202c; font-size: 20px; font-weight: 700;">Stock Health & Category Analytics</h3>
                                <p style="margin: 0 0 20px 0; color: #718096; font-size: 13px;">Real-time asset vulnerability trackers and category distribution summaries.</p>
                                
                                <div style="display: flex; flex-direction: column; gap: 20px;">
                                    
                                    <div style="border: 1px solid #edf2f7; border-radius: 10px; overflow: hidden;">
                                        <div style="background: #fff5f5; padding: 10px 14px; border-bottom: 1px solid #fed7d7; display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-exclamation-circle" style="color: #e53e3e; font-size: 14px;"></i>
                                            <span style="font-size: 13px; font-weight: 700; color: #9b2c2c;">Critical Stock Depletion Hazards (Top 5)</span>
                                        </div>
                                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                                            <thead>
                                                <tr style="background: #fafafa; border-bottom: 1px solid #edf2f7; color: #4a5568; font-weight: 600;">
                                                    <th style="padding: 10px 14px;">Product Hardware Asset</th>
                                                    <th style="padding: 10px 14px; text-align: center;">Current Stock</th>
                                                    <th style="padding: 10px 14px; text-align: right;">Unit Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($lowStockItems)): ?>
                                                    <tr><td colspan="3" style="padding: 15px; text-align: center; color: #a0aec0; font-weight: 600;">All inventory components clear of threshold parameters.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach($lowStockItems as $item): ?>
                                                        <tr style="border-bottom: 1px solid #f7fafc; color: #2d3748;">
                                                            <td style="padding: 10px 14px; font-weight: 600;"><?php echo htmlspecialchars($item['product_name'] ?? $item['name']); ?></td>
                                                            <td style="padding: 10px 14px; text-align: center;"><span style="background: #fff5f5; color: #c53030; padding: 2px 8px; border-radius: 12px; font-weight: 700; font-size: 12px; border: 1px solid #feb2b2;"><?php echo $item['quantity']; ?> left</span></td>
                                                            <td style="padding: 10px 14px; text-align: right; font-weight: 600; color: #4a5568;"><?php echo htmlspecialchars($currency_current) . number_format($item['price'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div style="border: 1px solid #edf2f7; border-radius: 10px; overflow: hidden;">
                                        <div style="background: #f8fafc; padding: 10px 14px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-layer-group" style="color: #4a5568; font-size: 14px;"></i>
                                            <span style="font-size: 13px; font-weight: 700; color: #2d3748;">Category Asset Holding Value Distribution</span>
                                        </div>
                                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                                            <thead>
                                                <tr style="background: #fafafa; border-bottom: 1px solid #edf2f7; color: #4a5568; font-weight: 600;">
                                                    <th style="padding: 10px 14px;">Category Label</th>
                                                    <th style="padding: 10px 14px; text-align: center;">SKUs</th>
                                                    <th style="padding: 10px 14px; text-align: center;">Volume</th>
                                                    <th style="padding: 10px 14px; text-align: right;">Gross Value Evaluation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($categoryReports)): ?>
                                                    <tr><td colspan="4" style="padding: 15px; text-align: center; color: #a0aec0; font-weight: 600;">No product categories detected inside tracking nodes.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach($categoryReports as $report): ?>
                                                        <tr style="border-bottom: 1px solid #f7fafc; color: #2d3748;">
                                                            <td style="padding: 10px 14px; font-weight: 700; color: #1a1a1a;"><?php echo htmlspecialchars($report['category'] ?? 'Uncategorized'); ?></td>
                                                            <td style="padding: 10px 14px; text-align: center; font-weight: 600; color: #718096;"><?php echo $report['total_skus']; ?> items</td>
                                                            <td style="padding: 10px 14px; text-align: center; font-weight: 600; color: #4a5568;"><?php echo number_format($report['stock_volume']); ?> units</td>
                                                            <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: #1a202c;"><?php echo htmlspecialchars($currency_current) . number_format($report['category_value'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div id="formActionsFooter" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f0f4f8; display: flex; justify-content: flex-end;">
                            <?php if ($isAdmin): ?>
                                <button type="submit" id="saveRulesBtn" style="background: #1a1a1a; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-save"></i> Save Preferences
                                </button>
                            <?php else: ?>
                                <div style="background: #fff8e1; color: #b78103; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                    <i class="fas fa-info-circle"></i> Read-Only: Administrative access required to modify settings parameters.
                                </div>
                            <?php endif; ?>
                        </div>

                    </form>
                </div>
            </div>
        </main>
    </div>

    <style>
        .tab-link { background: transparent; color: #4a5568; }
        .tab-link:hover { background: #edf2f7; color: #1a202c; }
        .tab-link.active { background: #1a1a1a !important; color: #fff !important; }
        input:focus { border-color: #1a1a1a !important; }
        
        /* Smooth scrolling adjustments for logs window */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>

    <script>
        function switchSettingsTab(evt, tabId) {
            const contents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < contents.length; i++) { contents[i].style.display = "none"; }
            
            const links = document.getElementsByClassName("tab-link");
            for (let i = 0; i < links.length; i++) { links[i].classList.remove("active"); }
            
            document.getElementById(tabId).style.display = "block";
            evt.currentTarget.classList.add("active");

            const footerBtn = document.getElementById('formActionsFooter');
            if(tabId === 'inventory-tab') {
                footerBtn.style.visibility = 'visible';
            } else {
                footerBtn.style.visibility = 'hidden';
            }
        }

        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('saveRulesBtn');
            if(!submitBtn) return;
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Saving...`;

            fetch('settings.php?action=save_settings', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                showToast(data.message, data.status === 'success' ? 'success' : 'error');
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                showToast('Network synchronization failed. Please try again.', 'error');
            });
        });

        function showToast(message, type) {
            const toast = document.getElementById('toastNotification');
            const icon = document.getElementById('toastIcon');
            const msgSpan = document.getElementById('toastMessage');

            msgSpan.textContent = message;
            toast.style.background = type === 'success' ? '#00b894' : '#d63031';
            icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-times-circle';

            toast.style.display = 'flex';
            setTimeout(() => { toast.style.transform = 'translateY(0)'; toast.style.opacity = '1'; }, 50);
            setTimeout(() => {
                toast.style.transform = 'translateY(-20px)';
                toast.style.opacity = '0';
                setTimeout(() => { toast.style.display = 'none'; }, 400);
            }, 3500);
        }
    </script>
</body>
</html>