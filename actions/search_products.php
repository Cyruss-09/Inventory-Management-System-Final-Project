<?php
session_start();

// 1. Authenticate user access
if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized access.");
}

// 2. Define the Admin check variable
$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';

// 3. Connect to database
include '../config/db.php'; 

// 4. Safely pull POST parameters from AJAX script
$queryStr = isset($_POST['query']) ? trim($_POST['query']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';

try {
    // 5. Build dynamic SQL search query
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    if ($queryStr !== '') {
        $sql .= " AND product_name LIKE :query";
        $params[':query'] = '%' . $queryStr . '%';
    }

    if ($category !== '') {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }

    $sql .= " ORDER BY id DESC";

    // 6. Define and execute statement variable ($stmt)
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // 7. Loop through results and print rows
    if ($stmt->rowCount() > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Define your row IDs and attributes inside the loop body
            $id = $row['id']; 
            $status = htmlspecialchars($row['status'] ?? 'Active');
            $badgeClass = (strtolower($status) == 'active') ? 'active' : 'out-of-stock';
            
            $pName = htmlspecialchars($row['product_name']);
            $pCat  = htmlspecialchars($row['category']);
            $pQty  = (int)$row['quantity'];
            $pPrice = (float)$row['price'];

            $imageName = !empty($row['image']) ? $row['image'] : 'product_placeholder.png';
            $imagePath = "../assets/img/products/" . $imageName;

            echo "<tr>
                    <td><input type='checkbox'></td>
                    <td class='product-cell'>
                        <img src='$imagePath' alt='$pName' style='width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px;'>
                        <span>" . $pName . "</span>
                    </td>
                    <td>" . $pCat . "</td>
                    <td style='text-align: center;'>" . $pQty . "</td>
                    <td><strong>₱" . number_format($pPrice, 2) . "</strong></td>
                    <td>" . htmlspecialchars($row['date_added']) . "</td>
                    <td><span class='badge {$badgeClass}'>" . $status . "</span></td>
                    <td>
                        <div style='display: flex; gap: 8px; justify-content: center;'>
                            <button type='button' class='action-btn btn-edit' onclick=\"openEditModal('$id', '$pName', '$pCat', '$pQty', '$pPrice', '$imagePath')\" style='background: #e1f5fe; color: #0288d1; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px;'>
                                <i class='fas fa-edit'></i> Edit
                            </button>";

            // 8. Safely apply $isAdmin condition and trigger the custom UI modal
            if ($isAdmin) {
                echo "      <button type='button' class='action-btn btn-delete' onclick=\"openDeleteModal('$id')\" style='background: #ffebee; color: #c62828; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px;'>
                                <i class='fas fa-trash'></i> Delete
                            </button>";
            }

            echo "      </div>
                    </td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='8' style='text-align:center; padding: 20px;'>No products found matching your search criteria.</td></tr>";
    }

} catch (PDOException $e) {
    echo "<tr><td colspan='8'>Error filtering items: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>