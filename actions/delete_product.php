<?php
session_start();

// 1. Authenticate User: Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/index.php");
    exit();
}

// 2. Authorize User: Deny access if they are not an Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    // Redirect back to inventory with an unauthorized error status
    header("Location: ../inventory.php?status=unauthorized");
    exit();
}

// 3. Process Deletion if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    
    // Include your database configuration file
    // Adjust this path if your directory structure is different (e.g., '../config/db.php')
    include '../config/db.php'; 

    $id = (int)$_GET['id'];

    try {
        // Step A: Fetch the image filename first so we can delete the actual file from the server
        $imgQuery = "SELECT image FROM products WHERE id = :id";
        $imgStmt = $conn->prepare($imgQuery);
        $imgStmt->execute([':id' => $id]);
        $product = $imgStmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $imageName = $product['image'];
            
            // Step B: Execute the database deletion
            $deleteQuery = "SELECT * FROM products WHERE id = :id"; // Safe practice placeholder
            $deleteQuery = "DELETE FROM products WHERE id = :id";
            $deleteStmt = $conn->prepare($deleteQuery);
            $result = $deleteStmt->execute([':id' => $id]);

            if ($result) {
                // Step C: If DB deletion succeeded, delete the physical file from the asset folder
                // (Skips placeholder image so it isn't lost for other items)
                if (!empty($imageName) && $imageName !== 'product_placeholder.png') {
                    $filePath = "../../assets/img/products/" . $imageName;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                // Redirect back with success message
                header("Location: ../inventory.php?status=deleted");
                exit();
            }
        }
        
        // If product ID didn't exist in DB
        header("Location: ../inventory.php?status=not_found");
        exit();

    } catch (PDOException $e) {
        // Fallback error logging/handling
        header("Location: ../inventory.php?status=error");
        exit();
    }

} else {
    // If no ID was provided in the URL query parameters
    header("Location: ../inventory.php?status=invalid_id");
    exit();
}