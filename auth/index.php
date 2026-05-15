<?php
session_start();
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$active_form = 'login'; // Default view state

// ==========================================
// HANDLE SIGNUP PROCESS
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'signup') {
    $active_form = 'signup'; // Keep signup visible if form reloads due to errors
    
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $user     = htmlspecialchars(trim($_POST['username']));
    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $pass     = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?status=invalid_email&view=signup");
        exit();
    }

    if ($pass !== $confirm_pass) {
        header("Location: index.php?status=mismatch&view=signup");
        exit();
    }

    if (strlen($pass) < 8) {
        header("Location: index.php?status=weak_password&view=signup");
        exit();
    }

    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO users (fullname, username, email, password, date_added) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullname, $user, $email, $hashed_password]);

        header("Location: index.php?status=success&view=login");
        exit();

    } catch(PDOException $e) {
        if ($e->getCode() == 23000) { 
            header("Location: index.php?status=exists&view=signup");
            exit();
        } else {
            die("Database Error: " . $e->getMessage()); 
        }
    }
}

// ==========================================
// HANDLE LOGIN PROCESS
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    $active_form = 'login';
    $user_input = $_POST['username']; 
    $pass = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$user_input, $user_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            header("Location: ../dashboard.php");
            exit();
        } else {
            header("Location: index.php?status=invalid&view=login");
            exit();
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Read URL state parameters for visual persistent layouts
if (isset($_GET['view']) && ($_GET['view'] == 'login' || $_GET['view'] == 'signup')) {
    $active_form = $_GET['view'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authentication - Cyruss Techgear Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Brand/Graphic Layout Side -->
        <div class="image-side">
            <img src="../assets/img/LOGO.png" alt="Cyruss Logo">
        </div>
        
        <!-- Animated Container Side -->
        <div class="form-side">
            
            <!-- LOGIN PANEL -->
            <div id="loginFormPanel" class="auth-form <?php echo ($active_form === 'login') ? '' : 'hidden-form'; ?>">
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <h1>Login</h1>
                    <div class="input-group">
                        <label>Username or Email</label>
                        <input type="text" name="username" required autocomplete="username" placeholder="Username or Email">
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="Password">
                    </div>
                    <div class="btn-container">
                        <button type="submit" class="submit-btn">Log in</button>
                    </div>
                    <div class="btn-container">
                        <p>Don't have an account? <a href="javascript:void(0)" onclick="toggleAuthForms('signup')">Sign up here</a></p>
                    </div>
                </form>
            </div>

            <!-- SIGNUP PANEL -->
            <div id="signupFormPanel" class="auth-form <?php echo ($active_form === 'signup') ? '' : 'hidden-form'; ?>">
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="signup">
                    <h1>Signup</h1>
                    <div class="input-group">
                        <label>Full Name:</label>
                        <input type="text" name="fullname" required autocomplete="name" placeholder="Full Name">
                    </div>
                    <div class="input-group">
                        <label>Username:</label>
                        <input type="text" name="username" required autocomplete="username" placeholder="Username">
                    </div>
                    <div class="input-group">
                        <label>Email:</label>
                        <input type="email" name="email" required autocomplete="email" placeholder="Email">
                    </div>
                    <div class="input-group">
                        <label>Password:</label>
                        <input type="password" name="password" required autocomplete="new-password" placeholder="Password">
                    </div>
                    <div class="input-group">
                        <label>Confirm Password:</label>
                        <input type="password" name="confirm_password" required autocomplete="new-password" placeholder="Confirm Password">
                    </div>
                    <div class="btn-container-signup">
                        <button type="submit" class="submit-btn">Sign Up</button>
                    </div>
                    <div class="btn-container-signup">
                        <p>Already have an account? <a href="javascript:void(0)" onclick="toggleAuthForms('login')">Login here</a></p>
                    </div>
                </form>
            </div>

            <!-- SHARED MODAL OVERLAY -->
            <div id="modalOverlay" class="modal-overlay">
                <div class="modal-box">
                    <div id="modalIcon" class="modal-icon"></div>
                    <h2 id="modalTitle"></h2>
                    <p id="modalMessage"></p>
                    <button type="button" onclick="closeModal()" class="submit-btn">OK</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Toggle Script Handles CSS Animations -->
    <script>
        function toggleAuthForms(viewTarget) {
            const loginPanel = document.getElementById('loginFormPanel');
            const signupPanel = document.getElementById('signupFormPanel');

            if (viewTarget === 'signup') {
                loginPanel.classList.add('hidden-form');
                signupPanel.classList.remove('hidden-form');
            } else {
                signupPanel.classList.add('hidden-form');
                loginPanel.classList.remove('hidden-form');
            }
            
            // Updates URL query parameter transparently without reloading the webpage
            const newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?view=' + viewTarget;
            window.history.pushState({path:newurl}, '', newurl);
        }
    </script>
    
    <!-- Load unified modal alert controllers -->
    <script src="../assets/js/modal-handler.js"></script>
</body>
</html>