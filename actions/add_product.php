<?php
session_start(); // Crucial to read the logged-in user's details

include '../config/db.php';

if (isset($_POST['submit'])) { 
    $name = $_POST['product_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    
    $imageName = 'product_placeholder.png'; 

    if (!empty($_FILES['product_image']['name'])) {
        $imageName = time() . '_' . $_FILES['product_image']['name']; 
        $targetPath = "../assets/img/products/" . $imageName;

        if(move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
            // File upload success
        }
    }

    try {
        // 1. Insert the product into the inventory table
        $sql = "INSERT INTO products (product_name, image, category, quantity, price, date_added) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $imageName, $category, $quantity, $price]);

        // 2. Log this execution into the Activity Audit Trail
        try {
            // Adjust the timezone matching your settings page log parser
            date_default_timezone_set('Asia/Manila'); 
            
            // Fall back to 'System' if username isn't saved to your session arrays yet
            $currentUser = $_SESSION['username'] ?? 'System'; 
            $actionText = "Added new inventory asset item: \"$name\" ($quantity units at ₱" . number_format($price, 2) . " each) under $category category.";

            $logSql = "INSERT INTO activity_log (username, action, timestamp) VALUES (?, ?, NOW())";
            $logStmt = $conn->prepare($logSql);
            $logStmt->execute([$currentUser, $actionText]);

        } catch (PDOException $logError) {
            // Left empty so that even if the logging fails, your application 
            // still completes the main product save uninterrupted.
        }

        // 3. Redirect to your UI management board
        header("Location: ../inventory.php?success=1");
        exit(); 

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: ../inventory.php");
    exit();
}