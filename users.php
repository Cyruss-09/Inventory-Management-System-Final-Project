<?php
session_start();
// Replace this with your actual database connection file path
include 'config/db.php'; 

$message = "";

// Handle Form Submission: Add User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $role     = $_POST['role'];
    
    // Securely hash a default temporary password (e.g., "Welcome123")
    $default_password = password_hash('Welcome123', PASSWORD_DEFAULT);
    $current_date     = date('Y-m-d H:i:s');

    if (!empty($username) && !empty($fullname) && !empty($email)) {
        try {
            // Using your exact SQL table columns
            $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, role, date_added) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fullname, $username, $email, $default_password, $role, $current_date]);
            
            $message = "<div style='color: #2ed573; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-check-circle'></i> User added successfully! Default pass: Welcome123</div>";
        } catch (PDOException $e) {
            $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-times-circle'></i> Error: Username or Email might already exist.</div>";
        }
    } else {
        $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'>Please fill out all fields.</div>";
    }
}

// Fetch all users using your exact schema
$usersStmt = $conn->query("SELECT id, fullname, username, email, role, date_added FROM users ORDER BY id DESC");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; min-height: 100vh;">
    
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
                <li class="active"><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li><a href="settings.php"><i class="fas fa-sliders-h"></i> Settings</a></li>
            </ul>

            <div class="logout-section">
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <main class="main-content" style="flex: 1; display: flex; justify-content: center; align-items: flex-start; padding: 40px; box-sizing: border-box;">
            
            <div style="width: 100%; max-width: 1100px; display: flex; flex-direction: column; gap: 20px;">
                
                <?php echo $message; ?>

                <div class="panel-card" style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #edf2f7; width: 100%; box-sizing: border-box;">
                    <h4 style="margin: 0 0 20px 0; font-size: 18px; color: #2d3436;"><i class="fas fa-users" style="color: #4a5568; margin-right: 8px;"></i> System Accounts Directory</h4>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid #edf2f7; color: #718096; font-weight: 600;">
                                    <th style="padding: 12px 8px;">Full Name</th>
                                    <th style="padding: 12px 8px;">Username</th>
                                    <th style="padding: 12px 8px;">Email Address</th>
                                    <th style="padding: 12px 8px;">Role Privileges</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr style="border-bottom: 1px solid #f7fafc;">
                                            <td style="padding: 14px 8px; font-weight: 500; color: #2d3436;"><?php echo htmlspecialchars($user['fullname']); ?></td>
                                            <td style="padding: 14px 8px; color: #4a5568;"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td style="padding: 14px 8px; color: #718096; font-size: 13px;"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td style="padding: 14px 8px;">
                                                <span style="background: <?php echo $user['role'] == 'Admin' ? '#feebcb; color: #c05621;' : '#edf2f7; color: #4a5568;'; ?> padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                                    <?php echo htmlspecialchars($user['role']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="padding: 30px; text-align: center; color: #a0aec0;">No alternative administrative accounts located.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>                      
            </div>
        </main>
    </div>
</body>
</html>