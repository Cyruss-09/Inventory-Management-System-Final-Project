<?php
// 1. Session start must be at the very top, before any HTML
session_start();
require_once '../config/db.php';

// If user is already logged in, send them to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input to prevent Cross-Site Scripting (XSS)
    $user_input = htmlspecialchars(trim($_POST['username'])); 
    $pass = $_POST['password'];

    try {
        // We use prepared statements to block SQL Injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$user_input, $user_input]);
        $user = $stmt->fetch();

        // Use password_verify to check against the hashed password in your DB
        if ($user && password_verify($pass, $user['password'])) {
            
            // Regenerate session ID to prevent Session Fixation attacks
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            header("Location: ../dashboard.php");
            exit();
        } else {
            // Generic error message prevents "Account Probing"
            header("Location: login.php?status=invalid");
            exit();
        }
    } catch(PDOException $e) {
        // In production, don't echo $e->getMessage() as it reveals DB structure
        error_log($e->getMessage());
        header("Location: login.php?status=error");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Cyruss Techgear Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="image-side">
            <img src="../assets/img/LOGO.png" alt="Cyruss Logo">
        </div>
        <div class="form-side">
            <!-- Action points to the same file -->
            <form action="login.php" method="POST">
                <h1>Login</h1>
                
                <div class="input-group">
                    <label>Username or Email</label>
                    <!-- Use type="text" to allow either username or email format -->
                    <input type="text" name="username" placeholder="Enter username or email" required autocomplete="username">
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter password" required autocomplete="current-password">
                </div>

                <div class="btn-container">
                    <button type="submit" class="submit-btn">Log in</button>
                </div>

                <div class="btn-container">
                    <p>Don't have an account? <a href="../auth/signup.php">Sign up here</a></p>
                </div>

                <!-- Modal for Error/Success Messages -->
                <div id="modalOverlay" class="modal-overlay">
                    <div class="modal-box">
                        <div id="modalIcon" class="modal-icon"></div>
                        <h2 id="modalTitle"></h2>
                        <p id="modalMessage"></p>
                        <button type="button" onclick="closeModal()" class="submit-btn">OK</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/modal-handler-login.js"></script>
</body>
</html>