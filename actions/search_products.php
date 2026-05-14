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
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $pName = htmlspecialchars($row['product_name']);
            $pCat = htmlspecialchars($row['category']);
            $pQty = (int)$row['quantity'];
            $pPrice = number_format($row['price'], 2);
            $status = htmlspecialchars($row['status'] ?? 'Active');
            $badgeClass = (strtolower($status) == 'active') ? 'active' : 'out-of-stock';
            $imagePath = "../assets/img/" . (!empty($row['image']) ? $row['image'] : 'product_placeholder.png');

            echo "<tr>
                    <td><input type='checkbox'></td>
                    <td class='product-cell'>
                        <img src='$imagePath' style='width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 10px;'>
                        <span>$pName</span>
                    </td>
                    <td>$pCat</td>
                    <td>$pQty</td>
                    <td><strong>₱$pPrice</strong></td>
                    <td>" . htmlspecialchars($row['date_added']) . "</td>
                    <td><span class='badge $badgeClass'>$status</span></td>
                    <td>
                         <div class='action-dropdown'>
                            <i class='fas fa-ellipsis-h more-icon' style='cursor:pointer;'></i>
                            <div class='dropdown-menu'>
                                <a href='javascript:void(0)' onclick=\"openEditModal('$id', '$pName', '$pCat', '$pQty', " . $row['price'] . ", '$imagePath')\">
                                    <i class='fas fa-edit'></i> Edit
                                </a>
                                <a href='actions/delete_product.php?id=$id' class='text-danger'>
                                    <i class='fas fa-trash'></i> Delete
                                </a>
                            </div>
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