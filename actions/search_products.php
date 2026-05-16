<?php
include '../config/db.php';

$query_val = isset($_POST['query']) ? trim($_POST['query']) : '';
$category_val = isset($_POST['category']) ? trim($_POST['category']) : '';

try {
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    if ($query_val !== '') {
        $sql .= " AND product_name LIKE ?";
        $params[] = "%$query_val%";
    }

    if ($category_val !== '') {
        $sql .= " AND category = ?";
        $params[] = $category_val;
    }

    $sql .= " ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
         while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
                            </button>
                            
                            <a href='actions/delete_product.php?id=$id' onclick='return confirm(\"Are you sure?\");' style='text-decoration: none;'>
                                <button type='button' class='action-btn btn-delete' style='background: #ffebee; color: #c62828; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px;'>
                                    <i class='fas fa-trash'></i> Delete
                                </button>
                            </a>
                        </div>
                    </td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='8' style='text-align:center; padding: 20px;'>No results found.</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='8'>Error: " . $e->getMessage() . "</td></tr>";
}
?>