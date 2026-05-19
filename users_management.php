<?php
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

$message = "";

/**
 * HELPER FUNCTION: Log System Activities
 * Automatically creates an audit trail entry in the activity_log table.
 */
function logActivity($conn, $username, $action) {
    try {
        // Ensure the activity_log table exists before trying to write to it
        $conn->exec("CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL,
            action TEXT NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $logStmt = $conn->prepare("INSERT INTO activity_log (username, action, timestamp) VALUES (?, ?, ?)");
        $logStmt->execute([$username, $action, date('Y-m-d H:i:s')]);
    } catch (PDOException $e) {
        // Fail silently so database log bugs don't crash core user operations
    }
}

// Handle Backend User Deletion Request (RESTRICTED TO ADMINS ONLY)
if (isset($_GET['delete_id'])) {
    if (!$isAdmin) {
        $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-times-circle'></i> Access Denied: Only administrators can remove accounts.</div>";
    } else {
        $delete_id = (int)$_GET['delete_id'];
        
        // 1. Prevent an administrator from deleting their own active session account
        if ($delete_id == $_SESSION['user_id']) {
            $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-exclamation-circle'></i> Safety Restriction: You cannot delete your own active administrator profile.</div>";
        } else {
            try {
                // Fetch target user details to inspect their assigned role profile
                $fetchUser = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
                $fetchUser->execute([$delete_id]);
                $targetUser = $fetchUser->fetch(PDO::FETCH_ASSOC);

                if (!$targetUser) {
                    $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-times-circle'></i> Error: User account not found.</div>";
                } 
                // 2. CRITICAL PROTECTION: Prevent admins from deleting other admin accounts
                elseif (strtolower($targetUser['role']) === 'admin') {
                    $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-shield-alt'></i> Protection Error: Peer Administrative accounts cannot be removed by other administrators.</div>";
                } else {
                    $targetUsername = $targetUser['username'];

                    // Proceed with standard deletion sequence since the target is a regular staff member
                    $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $deleteStmt->execute([$delete_id]);
                    
                    // SUCCESS LOG TRIGGER
                    $currentUser = $_SESSION['username'] ?? 'Admin';
                    logActivity($conn, $currentUser, "Permanently removed employee account: ($targetUsername) from system directory.");

                    $message = "<div style='color: #2ed573; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-check-circle'></i> User account successfully removed from directory.</div>";
                }
            } catch (PDOException $e) {
                $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-times-circle'></i> Operational Error: Failed to execute deletion command.</div>";
            }
        }
    }
}

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
            $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, role, date_added) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fullname, $username, $email, $default_password, $role, $current_date]);
            
            // SUCCESS LOG TRIGGER
            $currentUser = $_SESSION['username'] ?? 'Admin';
            logActivity($conn, $currentUser, "Registered new account: '$username' assigned with [$role] privileges.");

            $message = "<div style='color: #2ed573; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-check-circle'></i> User added successfully! Default pass: Welcome123</div>";
        } catch (PDOException $e) {
            $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'><i class='fas fa-times-circle'></i> Error: Username or Email might already exist.</div>";
        }
    } else {
        $message = "<div style='color: #e53935; margin-bottom: 15px; font-weight: 600;'>Please fill out all fields.</div>";
    }
}

// Fetch ALL users cleanly to display across the directory
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
                <li class="active"><a href="users_management.php"><i class="fas fa-users-cog"></i> User Management</a></li>
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
                                    <th style="padding: 12px 8px; text-align: center;">Account Status</th>
                                    <th style="padding: 12px 8px; text-align: center;">Actions</th> </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr style="border-bottom: 1px solid #f7fafc;">
                                            <td style="padding: 14px 8px; font-weight: 500; color: #2d3436;"><?php echo htmlspecialchars($user['fullname']); ?></td>
                                            <td style="padding: 14px 8px; color: #4a5568;"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td style="padding: 14px 8px; color: #718096; font-size: 13px;"><?php echo htmlspecialchars($user['email']); ?></td>
                                            
                                            <td style="padding: 14px 8px;">
                                                <?php if (strtolower($user['role']) === 'admin'): ?>
                                                    <span style="background: #feebcb; color: #c05621; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                                        Admin
                                                    </span>
                                                <?php else: ?>
                                                    <span style="background: #e2e8f0; color: #4a5568; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                                        <?php echo htmlspecialchars($user['role']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td style="padding: 14px 8px; text-align: center;">
                                                <span style="background: #e6fffa; color: #006d5b; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                                    Active
                                                </span>
                                            </td>

                                            <td style="padding: 14px 8px; text-align: center;">
                                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                    <span style="color: #718096; font-size: 12px; font-style: italic; font-weight: 600;"><i class="fas fa-user-shield"></i> You</span>
                                                <!-- UI PROTECTION LAYER: Hide "Remove" button if the table row item belongs to an admin profile -->
                                                <?php elseif (strtolower($user['role']) === 'admin'): ?>
                                                    <span style="color: #e67e22; font-size: 12px; font-weight: 600;"><i class="fas fa-shield-alt"></i> Protected Admin</span>
                                                <?php elseif ($isAdmin): ?>
                                                    <button type="button" onclick="openDeleteUserModal('<?php echo $user['id']; ?>')" style="background: #ffebee; color: #c62828; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; font-size: 13px;">
                                                        <i class="fas fa-trash-alt"></i> Remove
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color: #cbd5e0; font-size: 12px; font-style: italic;"><i class="fas fa-lock"></i> Secured</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" style="padding: 30px; text-align: center; color: #a0aec0;">No registered user accounts found in the directory.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>                      
            </div>
        </main>
    </div>

    <?php if ($isAdmin): ?>
    <div id="deleteUserModal" class="modal-overlay" style="display:none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(3px);">
        <div style="background-color: #fff; margin: 12% auto; padding: 30px; width: 360px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); text-align: center; animation: modalPopIn 0.3s ease;">
            
            <div style="background: #ffebee; color: #d32f2f; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 24px;">
                <i class="fas fa-user-times"></i>
            </div>

            <h3 style="margin: 0 0 10px 0; color: #2d3436; font-size: 20px; font-weight: 700;">Remove Account?</h3>
            <p style="margin: 0 0 25px 0; color: #636e72; font-size: 14px; line-height: 1.5;">Are you sure you want to remove this employee from the system directory? This will immediately terminate their dashboard access privileges.</p>
            
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" onclick="closeDeleteUserModal()" style="flex: 1; background: #f5f6fa; color: #2d3436; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                    Cancel
                </button>
                <a id="confirmUserDeleteBtn" href="#" style="flex: 1; text-decoration: none;">
                    <button type="button" style="width: 100%; background: #d32f2f; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                        Delete
                    </button>
                </a>
            </div>
        </div>
    </div>

    <style>
    @keyframes modalPopIn {
        from { opacity: 0; transform: scale(0.95); transform: translateY(-10px); }
        to { opacity: 1; transform: scale(1); transform: translateY(0); }
    }
    </style>

    <script>
        function openDeleteUserModal(userId) {
            document.getElementById('confirmUserDeleteBtn').href = `users_management.php?delete_id=${userId}`;
            document.getElementById('deleteUserModal').style.display = "block";
        }

        function closeDeleteUserModal() {
            document.getElementById('deleteUserModal').style.display = "none";
        }

        window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteUserModal');
            if (event.target === deleteModal) {
                closeDeleteUserModal();
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>