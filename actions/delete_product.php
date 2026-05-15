<?php
// 1. ALWAYS start the session and check authentication first (Security!)
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Include database connection
// Adjust this path depending on where delete_product.php is located relative to db.php
require_once '../config/db.php'; 

// 3. Check if ID is provided in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // --- STEP A: Fetch the image filename from the database before deleting the row ---
        $imgQuery = "SELECT image FROM products WHERE id = :id";
        $imgStmt = $conn->prepare($imgQuery);
        $imgStmt->execute([':id' => $id]);
        $product = $imgStmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $imageName = $product['image'];
            
            // Define the target directory where images are stored
            $targetDir = "../assets/img/products/";
            $filePath = $targetDir . $imageName;

            // --- STEP B: Delete the physical file if it exists ---
            // Make sure you don't accidently delete your default placeholder image!
            if (!empty($imageName) && $imageName !== 'product_placeholder.png') {
                if (file_exists($filePath)) {
                    unlink($filePath); // This deletes the physical file from the folder
                }
            }
        }

        // --- STEP C: Now delete the record from the database ---
        $deleteQuery = "DELETE FROM products WHERE id = :id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->execute([':id' => $id]);

        // Redirect back to inventory with a success message
        header("Location: ../inventory.php?status=deleted");
        exit();

    } catch (PDOException $e) {
        // Handle database errors gracefully
        die("Error deleting product: " . $e->getMessage());
    }
} else {
    // If no ID was passed, redirect back
    header("Location: ../inventory.php");
    exit();
}