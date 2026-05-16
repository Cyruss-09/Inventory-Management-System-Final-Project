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

        // Verifies user existence and tests the hashed password structure cleanly
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            header("Location: ../dashboard.php");
            exit();
        } else {
            // Redirects with exact flag for incorrect password/username combinations
            header("Location: index.php?status=invalid_password&view=login");
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

// Extract any system status codes waiting in the GET query string parameters
$statusFlag = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authentication - Cyruss Techgear Hub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="image-side">
            <img src="../assets/img/LOGO.png" alt="Cyruss Logo">
        </div>
        
        <div class="form-side">
            
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

            <div id="modalOverlay" class="modal-overlay" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
                <div class="modal-box" style="background:#fff; padding:30px; border-radius:12px; max-width:280px; width:90%; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1); margin: 20px;">
                    <div id="modalIcon" class="modal-icon" style="font-size: 45px; margin-bottom: 15px;"></div>
                    <h2 id="modalTitle" style="margin: 0 0 10px 0; font-size:22px; color:#333;"></h2>
                    <p id="modalMessage" style="margin: 0 0 20px 0; color:#666; font-size:14px; line-height:1.5;"></p>
                    <button type="button" onclick="closeModal()" class="submit-btn" style="width:100%; padding:12px; border:none; background:#2d3436; color:#fff; border-radius:6px; cursor:pointer; font-weight:600;">OK</button>
                </div>
            </div>

        </div>
    </div>

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
            
            const newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?view=' + viewTarget;
            window.history.pushState({path:newurl}, '', newurl);
        }

        // Native client modal visibility controllers
        function showModal(title, message, isError = true) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalMessage').innerText = message;
            
            const iconContainer = document.getElementById('modalIcon');
            if(isError) {
                iconContainer.innerHTML = '<i class="fas fa-times-circle" style="color:#e53935;"></i>';
            } else {
                iconContainer.innerHTML = '<i class="fas fa-check-circle" style="color:#2ed573;"></i>';
            }
            
            document.getElementById('modalOverlay').style.display = "flex";
        }

        function closeModal() {
            document.getElementById('modalOverlay').style.display = "none";
            // Clean URL status params clean after closing
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?view=<?php echo $active_form; ?>';
            window.history.pushState({path:cleanUrl}, '', cleanUrl);
        }

        // Intercept backend triggers immediately when document objects bind
        document.addEventListener("DOMContentLoaded", function() {
            const backendStatus = "<?php echo $statusFlag; ?>";
            
            if (backendStatus === "invalid_password") {
                showModal("Access Denied", "The password or username you entered is incorrect. Please try again.", true);
            } else if (backendStatus === "mismatch") {
                showModal("Password Mismatch", "Your password validation fields do not match.", true);
            } else if (backendStatus === "weak_password") {
                showModal("Weak Password", "Security failure: Registration passwords must contain at least 8 characters.", true);
            } else if (backendStatus === "invalid_email") {
                showModal("Invalid Email", "Please enter a correctly formatted email address structure.", true);
            } else if (backendStatus === "exists") {
                showModal("Account Conflict", "This username or email address is already registered to a manager account.", true);
            } else if (backendStatus === "success") {
                showModal("Registration Successful!", "Your account has been created. You can log in now.", false);
            }
        });
    </script>
</body>
</html>