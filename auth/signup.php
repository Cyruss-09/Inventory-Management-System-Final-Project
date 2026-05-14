<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $user     = htmlspecialchars(trim($_POST['username']));
    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $pass     = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: signup.php?status=invalid_email");
        exit();
    }

    if ($pass !== $confirm_pass) {
        header("Location: signup.php?status=mismatch");
        exit();
    }

    if (strlen($pass) < 8) {
        header("Location: signup.php?status=weak_password");
        exit();
    }

    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    try {
        // We ensure date_added is included
        $sql = "INSERT INTO users (fullname, username, email, password, date_added) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullname, $user, $email, $hashed_password]);

        header("Location: login.php?status=success");
        exit();

    } catch(PDOException $e) {
        if ($e->getCode() == 23000) { 
            header("Location: signup.php?status=exists");
            exit();
        } else {
            // DEBUG MODE: This will stop the redirect and show the REAL error
            // Once fixed, change this back to: header("Location: signup.php?status=error");
            die("Database Error: " . $e->getMessage()); 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Signup - Cyruss Techgear Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container" style="flex-direction: row-reverse;">
        <div class="image-side">
            <img src="../assets/img/LOGO.png" alt="Cyruss Logo">
        </div>
        <div class="form-side">
            <form action="signup.php" method="POST">
                <h1>Signup</h1>
                
                <div class="input-group">
                    <label>Full Name:</label>
                    <input type="text" name="fullname" required autocomplete="name">
                </div>
                
                <div class="input-group">
                    <label>Username:</label>
                    <input type="text" name="username" required autocomplete="username">
                </div>
                
                <div class="input-group">
                    <label>Email:</label>
                    <input type="email" name="email" required autocomplete="email">
                </div>
                
                <div class="input-group">
                    <label>Password:</label>
                    <input type="password" name="password" required autocomplete="new-password">
                </div>
                
                <div class="input-group">
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" required autocomplete="new-password">
                </div>
                
                <div class="btn-container-signup">
                    <button type="submit" class="submit-btn">Sign Up</button>
                </div>
                
                <div class="btn-container-signup">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>

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
    <script src="../assets/js/modal-handler-signup.js"></script>
</body>
</html>