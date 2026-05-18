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
        $query = "DELETE FROM products WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $id]);
        
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