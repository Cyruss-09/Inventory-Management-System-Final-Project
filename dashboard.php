<?php
session_start();

// If the session variable 'user_id' is NOT set, the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Send them back to the login page immediately
    header("Location: auth/index.php");
    exit();
}

include 'config/db.php';

try {
    // 1. Count Total Unique Products
    $totalProductsStmt = $conn->query("SELECT COUNT(*) FROM products");
    $totalProducts = $totalProductsStmt->fetchColumn();

    // 2. Sum Total Quantity (Total Stocks)
    $totalStocksStmt = $conn->query("SELECT SUM(quantity) FROM products");
    $totalStocks = $totalStocksStmt->fetchColumn() ?: 0; // Default to 0 if null

    // 3. Count Low Stock Items (Threshold: less than 10)
    $lowStockStmt = $conn->query("SELECT COUNT(*) FROM products WHERE quantity < 10");
    $lowStock = $lowStockStmt->fetchColumn();

    // 4. Count Recently Added (Added in the last 7 days)
    $recentStmt = $conn->query("SELECT COUNT(*) FROM products WHERE date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recentAdded = $recentStmt->fetchColumn();

    // 5. Calculate Total Financial Inventory Asset Value
    $assetStmt = $conn->query("SELECT SUM(price * quantity) FROM products");
    $totalAssetValue = $assetStmt->fetchColumn() ?: 0;

    // 6. Fetch Category Distribution for Chart.js
    $chartStmt = $conn->query("SELECT category, SUM(quantity) as total_qty FROM products GROUP BY category");
    $chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data arrays for JavaScript consumption
    $categoriesJson = json_encode(array_column($chartData, 'category'));
    $quantitiesJson = json_encode(array_column($chartData, 'total_qty'));

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body style="background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0;">

    <div class="container">
        <nav class="sidebar">
            <div class="logo-section">
                <div class="logo-circle">
                    <img src="../assets/img/LOGO.png" alt="Cyruss Logo" class="logo-img">
                </div>
            </div>

            <ul class="nav-links">
                <li class="active"><a href="#"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li><a href="settings.php"><i class="fas fa-sliders-h"></i> Settings</a></li>
            </ul>

            <div class="logout-section">
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <main class="main-content" style="padding: 30px; box-sizing: border-box;">
            
            <header class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px;">
                <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="background: #e3f2fd; color: #1e88e1; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <span style="color: #888; font-size: 13px; display: block;">Total Products</span>
                        <h3 style="margin: 5px 0 0 0; font-size: 22px; color: #2d3436;"><?php echo $totalProducts; ?></h3>
                    </div>
                </div>
                
                <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="background: #e8f5e9; color: #43a047; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-cubes"></i></div>
                    <div class="stat-info">
                        <span style="color: #888; font-size: 13px; display: block;">Total Stocks</span>
                        <h3 style="margin: 5px 0 0 0; font-size: 22px; color: #2d3436;"><?php echo $totalStocks; ?></h3>
                    </div>
                </div>

                <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon red-icon" style="background: #ffebee; color: #e53935; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-arrow-trend-down"></i></div>
                    <div class="stat-info">
                        <span style="color: #888; font-size: 13px; display: block;">Low Stock Items</span>
                        <h3 style="margin: 5px 0 0 0; font-size: 22px; color: #2d3436;"><?php echo $lowStock; ?></h3>
                    </div>
                </div>

                <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="background: #f3e5f5; color: #8e24aa; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-clipboard-check"></i></div>
                    <div class="stat-info">
                        <span style="color: #888; font-size: 13px; display: block;">Recently Added</span>
                        <h3 style="margin: 5px 0 0 0; font-size: 22px; color: #2d3436;"><?php echo $recentAdded; ?></h3>
                    </div>
                </div>

                <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="background: #fff8e1; color: #ffb300; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;"><i class="fas fa-wallet"></i></div>
                    <div class="stat-info">
                        <span style="color: #888; font-size: 13px; display: block;">Total Asset Value</span>
                        <h3 style="margin: 5px 0 0 0; font-size: 20px; color: #2d3436; font-weight: 700;">₱<?php echo number_format($totalAssetValue, 2); ?></h3>
                    </div>
                </div>
            </header>

            <hr class="divider" style="border: 0; border-top: 1px solid #e0e0e0; margin: 25px 0;">

            <div class="dashboard-workspace" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 25px; min-height: 380px;">
                
                <div class="data-lists-pane" style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <div class="panel-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h4 style="margin: 0; font-size: 16px; color: #2d3436; font-weight: 600;"><i class="fas fa-triangle-exclamation" style="color: #e53935; margin-right: 8px;"></i> Critical Stock Alert</h4>
                            <a href="inventory.php" style="font-size: 12px; color: #1e88e1; text-decoration: none; font-weight: 600;">Manage Inventory →</a>
                        </div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead>
                                <tr style="border-bottom: 2px solid #f5f6fa; text-align: left; color: #a0a5b5; font-weight: 600;">
                                    <th style="padding: 10px 0;">Product Name</th>
                                    <th>Category</th>
                                    <th style="text-align: center;">In Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $lowItemsStmt = $conn->query("SELECT product_name, category, quantity FROM products WHERE quantity < 10 ORDER BY quantity ASC LIMIT 4");
                                    if ($lowItemsStmt->rowCount() > 0) {
                                        while($lowRow = $lowItemsStmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<tr style='border-bottom: 1px solid #fcfcfd;'>
                                                    <td style='padding: 12px 0; font-weight: 500; color: #2d3436;'>".htmlspecialchars($lowRow['product_name'])."</td>
                                                    <td><span style='background: #f1f2f6; color:#57606f; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;'>".htmlspecialchars($lowRow['category'])."</span></td>
                                                    <td style='text-align: center; color: #e53935; font-weight: 700;'>".$lowRow['quantity']." items</td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' style='padding: 20px 0; text-align: center; color: #a0a5b5;'>Warehouse healthy. No critical shortages.</td></tr>";
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="panel-card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h4 style="margin: 0; font-size: 16px; color: #2d3436; font-weight: 600;"><i class="fas fa-clock-rotate-left" style="color: #2d3436; margin-right: 8px;"></i> Recent Arrivals Feed</h4>
                            <span style="font-size: 11px; background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 30px; font-weight: 600;">Rolling Logs</span>
                        </div>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 14px;">
                            <?php
                                $recentItemsStmt = $conn->query("SELECT product_name, date_added, price FROM products ORDER BY id DESC LIMIT 3");
                                if ($recentItemsStmt->rowCount() > 0) {
                                    while($recRow = $recentItemsStmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<li style='display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #fbfbfb;'>
                                                <div>
                                                    <div style='font-weight: 500; color: #2d3436;'>".htmlspecialchars($recRow['product_name'])."</div>
                                                    <small style='color: #a0a5b5; font-size: 11px;'><i class='far fa-calendar-alt'></i> Entry: ".$recRow['date_added']."</small>
                                                </div>
                                                <span style='font-weight: 700; color: #2ed573;'>₱".number_format($recRow['price'], 2)."</span>
                                              </li>";
                                    }
                                } else {
                                    echo "<li style='padding: 20px 0; text-align: center; color: #a0a5b5;'>No records logged this week.</li>";
                                }
                            ?>
                        </ul>
                    </div>
                </div>

                <div class="chart-pane" style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); display: flex; flex-direction: column; align-items: center; justify-content: space-between;">
                    <div style="width: 100%; margin-bottom: 10px; text-align: left;">
                        <h4 style="margin: 0; font-size: 16px; color: #2d3436; font-weight: 600;"><i class="fas fa-chart-pie" style="margin-right: 8px; color: #4b6584;"></i> Stock Distribution</h4>
                    </div>
                    
                    <div style="width: 240px; height: 240px; position: relative;">
                        <canvas id="categoryDoughnutChart"></canvas>
                    </div>
                    
                    <div style="font-size: 11px; color: #a0a5b5; margin-top: 10px; text-align: center;">
                        <p style="margin:0;">Proportionate metrics based on physical inventory volume count.</p>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('categoryDoughnutChart').getContext('2d');
            
            // Ingest arrays natively rendered by PHP
            const labelsArray = <?php echo $categoriesJson; ?>;
            const dataArray = <?php echo $quantitiesJson; ?>;

            if(labelsArray.length === 0) {
                ctx.font = "14px Arial";
                ctx.fillStyle = "#aaa";
                ctx.textAlign = "center";
                ctx.fillText("No data items available.", 120, 120);
                return;
            }

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labelsArray,
                    datasets: [{
                        data: dataArray,
                        backgroundColor: [
                            '#ff6b6b', '#feca57', '#1dd1a1', '#ff9ff3', '#54a0ff',
                            '#5f27cd', '#00d2d3', '#48dbfb', '#ff9f43'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { size: 11, family: 'Segoe UI' },
                                padding: 10
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        });
    </script>
</body>
</html>