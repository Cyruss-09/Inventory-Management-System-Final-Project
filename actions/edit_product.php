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
    header("Location: ../inventory.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../assets/css/inventory.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px 20px;">

    <div class="product-container" style="background: #fff; padding: 30px; max-width: 500px; margin: auto; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
        <h2 style="margin-top: 0; color: #333; font-size: 24px;">Edit Product</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Updating: <strong><?= htmlspecialchars($product['product_name']) ?></strong></p>
        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">

        <form action="../actions/update_product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 500; margin-bottom: 5px; color: #444;">Product Name:</label>
                <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 500; margin-bottom: 5px; color: #444;">Category:</label>
                <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; background-color: #fff; height: 42px;">
                    <option value="GPU" <?= ($product['category'] == 'GPU') ? 'selected' : '' ?>>GPU</option>
                    <option value="CPU" <?= ($product['category'] == 'CPU') ? 'selected' : '' ?>>CPU</option>
                    <option value="RAM" <?= ($product['category'] == 'RAM') ? 'selected' : '' ?>>RAM</option>
                    <option value="MOTHERBOARD" <?= ($product['category'] == 'MOTHERBOARD') ? 'selected' : '' ?>>MOTHERBOARD</option>
                    <option value="HDD" <?= ($product['category'] == 'HDD') ? 'selected' : '' ?>>HDD</option>
                    <option value="SSD" <?= ($product['category'] == 'SSD') ? 'selected' : '' ?>>SSD</option>
                    <option value="PSU" <?= ($product['category'] == 'PSU') ? 'selected' : '' ?>>PSU</option>
                    <option value="CASE" <?= ($product['category'] == 'CASE') ? 'selected' : '' ?>>CASE</option>
                    <option value="PERIPHERALS" <?= ($product['category'] == 'PERIPHERALS') ? 'selected' : '' ?>>PERIPHERALS</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 500; margin-bottom: 5px; color: #444;">Quantity:</label>
                <input type="number" name="quantity" min="0" value="<?= $product['quantity'] ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-weight: 500; margin-bottom: 5px; color: #444;">Price (₱):</label>
                <input type="number" step="0.01" name="price" min="0" value="<?= $product['price'] ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="text-align: right; display: flex; justify-content: flex-end; gap: 10px; align-items: center;">
                <a href="../inventory.php" style="text-decoration: none; background: #eee; color: #333; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; transition: 0.2s;">Cancel</a>
                <button type="submit" name="update" style="background: #2d3436; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: 0.2s;">Save Changes</button>
            </div>
        </form>
    </div>

</body>
</html>

<?