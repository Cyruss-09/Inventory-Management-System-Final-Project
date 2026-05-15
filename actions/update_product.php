<?php
include '../config/db.php';

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['product_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    
    // Check if a new image was uploaded
    if (!empty($_FILES['product_image']['name'])) {
        $fileName = time() . '_' . $_FILES['product_image']['name']; 
        $targetPath = "../assets/img/products/" . $fileName;

        // FIX: The temporary file index is 'tmp_name', not 'tmp_tmp'
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
            // Update WITH new image
            $sql = "UPDATE products SET product_name=?, category=?, quantity=?, price=?, image=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $category, $quantity, $price, $fileName, $id]);
        } else {
            // If upload fails, still update the text data
            $sql = "UPDATE products SET product_name=?, category=?, quantity=?, price=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $category, $quantity, $price, $id]);
        }
    } else {
        // Update WITHOUT changing the image
        $sql = "UPDATE products SET product_name=?, category=?, quantity=?, price=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $category, $quantity, $price, $id]);
    }

    // Redirect back to inventory
    header("Location: ../inventory.php?success=1");
    exit();
}
?>