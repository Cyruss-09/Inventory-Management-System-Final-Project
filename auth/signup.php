<?php
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $user = $_POST['username'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // Basic validation
    if ($pass !== $confirm_pass) {
        die("Passwords do not match.");
    }

    // Securely hash the password
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

        try {
        $sql = "INSERT INTO users (fullname, username, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullname, $user, $email, $hashed_password]);
        // Redirect with a success parameter
        header("Location: login.php?status=success");
        exit();
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) { 
            // Redirect with an error parameter
            header("Location: signup.php?status=exists");
            exit();
        } else {
            echo "Error: " . $e->getMessage();
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
    <!-- Inline style to reverse the row direction specifically for signup -->
    <div class="container" style="flex-direction: row-reverse;">
        <div class="image-side">
            <img src="../assets/img/LOGO.png" alt="Cyruss Logo">
        </div>
        <div class="form-side">
            <form action="../auth/signup.php" method="POST">
                <h1>Signup</h1>
                <div class="input-group">
                    <label>Full Name:</label>
                    <input type="text" name="fullname" required>
                </div>
                <div class="input-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <div class="input-group">
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" required>
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
                        <button onclick="closeModal()" class="submit-btn">OK</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/modal-handler-signup.js"></script>
</body>
</html>