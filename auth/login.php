<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = $_POST['username']; // Can be username or email
    $pass = $_POST['password'];

        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$user_input, $user_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                // Password is correct, start a session
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                header("Location: ../dashboard.php");
                exit();
            } else {
                // Redirect back to login with an error status
                header("Location: login.php?status=invalid");
                exit();
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
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
            <form action="../auth/login.php" method="POST">
                <h1>Login</h1>
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="btn-container">
                    <button type="submit" class="submit-btn">Log in</button>
                </div>
                <div class="btn-container">
                    <p>Don't have an account? <a href="../auth/signup.php">Sign up here</a></p>
                </div>
                <div id="modalOverlay" class="modal-overlay">
                    <div class="modal-box">
                        <div id="modalIcon" class="modal-icon"></div>
                        <h2 id="modalTitle"></h2>
                        <p id="modalMessage"></p>
                        <button onclick="closeModal()" class="submit-btn">OK</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/modal-handler-login.js"></script>
</body>
</html>