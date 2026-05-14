<?php
// Include the database connection from your config folder
include '../config/db.php'; 

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Product not found in database.");
    }
} else {
    header("Location: inventory.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../assets/css/inventory.css">
</head>
<body>
    <div class="product-container" style="padding: 50px; max-width: 600px; margin: auto;">
        <h2>Edit Product: <?= htmlspecialchars($product['product_name']) ?></h2>
        <form action="../actions/update_product.php" method="POST">
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
            
            <label>Product Name</label><br>
            <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required style="width:100%; margin-bottom:10px; padding:8px;"><br>

            <label>Category</label><br>
            <select name="category" style="width:100%; padding:8px;">
                <option value="GPU" <?= ($product['category'] == 'GPU') ? 'selected' : '' ?>>GPU</option>
                <option value="CPU" <?= ($product['category'] == 'CPU') ? 'selected' : '' ?>>CPU</option>
                <option value="RAM" <?= ($product['category'] == 'RAM') ? 'selected' : '' ?>>RAM</option>
                <option value="MOTHERBOARD" <?= ($product['category'] == 'MOTHERBOARD') ? 'selected' : '' ?>>MOTHERBOARD</option>
                <option value="HDD" <?= ($product['category'] == 'HDD') ? 'selected' : '' ?>>HDD</option>
                <option value="SSD" <?= ($product['category'] == 'SSD') ? 'selected' : '' ?>>SSD</option>
            </select><br><br>

            <label>Quantity</label><br>
            <input type="number" name="quantity" value="<?= $product['quantity'] ?>" required style="width:100%; margin-bottom:10px; padding:8px;"><br>

            <label>Price</label><br>
            <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required style="width:100%; margin-bottom:20px; padding:8px;"><br>

            <button type="submit" name="update" style="background:#2d3436; color:white; padding:10px 20px; border:none; border-radius:4px; cursor:pointer;">Save Changes</button>
            <a href="../inventory.php" style="margin-left:10px; color:#666;">Cancel</a>
        </form>
    </div>
</body>
</html>