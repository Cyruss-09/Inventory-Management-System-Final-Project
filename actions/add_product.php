<?php
include '../config/db.php';

if (isset($_POST['submit'])) { 
    $name = $_POST['product_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    
    $imageName = 'product_placeholder.png'; 

    if (!empty($_FILES['product_image']['name'])) {
        $imageName = time() . '_' . $_FILES['product_image']['name']; 
        $targetPath = "../assets/img/" . $imageName;

        if(move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
            // File upload success
        }
    }

    try {
        // We include date_added in the columns and use NOW() in the values
        $sql = "INSERT INTO products (product_name, image, category, quantity, price, date_added) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        
        // Execute with only the 5 variables from your form
        $stmt->execute([$name, $imageName, $category, $quantity, $price]);

        header("Location: ../inventory.php?success=1");
        exit(); 

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: ../inventory.php");
    exit();
}