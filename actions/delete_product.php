<?php
session_start();

// 1. Check if the user is even logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/index.php"); 
    exit();
}

// 2. Check if the logged-in user is an Admin
$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';

if (!$isAdmin) {
    // Kick them out or show an error if they try to access this file maliciously
    header("Location: ../inventory.php?error=unauthorized");
    exit();
}

// 3. If they pass the checks, proceed with deletion
if (isset($_GET['id'])) {
    include '../config/db.php';
    
    $id = $_GET['id'];
    
    try {
        // --- STEP A: FETCH PRODUCT NAME BEFORE DELETING IT ---
        $productName = "Unknown Item"; 
        try {
            $fetchQuery = "SELECT product_name FROM products WHERE id = :id";
            $fetchStmt = $conn->prepare($fetchQuery);
            $fetchStmt->execute([':id' => $id]);
            $product = $fetchStmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                $productName = $product['product_name'];
            }
        } catch (PDOException $fetchError) {
            // Keep going even if name retrieval fails
        }
        // -----------------------------------------------------

        // 4. Run the actual item deletion query
        $query = "DELETE FROM products WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $id]);
        
        // --- STEP B: LOG THE DELETION EVENT ---
        try {
            date_default_timezone_set('Asia/Manila');
            
            $currentUser = $_SESSION['username'] ?? 'System';
            $actionText = "Permanently deleted inventory product: \"$productName\" (Item Record ID: #$id)";

            $logSql = "INSERT INTO activity_log (username, action, timestamp) VALUES (?, ?, NOW())";
            $logStmt = $conn->prepare($logSql);
            $logStmt->execute([$currentUser, $actionText]);
            
        } catch (PDOException $logError) {
            // Silence log-specific database errors to protect script execution flow
        }
        // -------------------------------------

        header("Location: ../inventory.php?success=deleted");
        exit();
    } catch (PDOException $e) {
        header("Location: ../inventory.php?error=db_error");
        exit();
    }
} else {
    header("Location: ../inventory.php");
    exit();
}
?>