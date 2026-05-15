<?php
// 1. Start the session to gain access to it
session_start();

// 2. Unset all session variables
$_SESSION = array();

// 3. Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy the session on the server
session_destroy();

// 5. Redirect the user to the login page
// Change 'login.php' to your actual login filename if it's different
header("location: ../auth/index.php");
exit;
?>