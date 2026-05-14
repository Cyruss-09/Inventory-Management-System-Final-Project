<?php
// 1. Include the DB connection at the very top
include 'config/db.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/inventory.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo-section">
                <div class="logo-circle">
                    <img src="../assets/img/LOGO.png" alt="Logo" class="logo-img">
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li class="active"><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
            </ul>

            <div class="logout-section">
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="product-container">
                <h2>Products</h2>
                
                <!-- Toolbar Section -->
                <div class="toolbar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search products...">
                        
                        <!-- Integrated Category Filter -->
                        <select id="categoryFilter" style="border: none; background: transparent; cursor: pointer; padding: 5px; border-left: 1px solid #ddd; margin-left: 10px;">
                            <option value="">All Categories</option>
                            <option value="GPU">GPU</option>
                            <option value="CPU">CPU</option>
                            <option value="RAM">RAM</option>
                            <option value="MOTHERBOARD">MOTHERBOARD</option>
                            <option value="HDD">HDD</option>
                            <option value="SSD">SSD</option>
                            <option value="PERIPHERALS">PERIPHERALS</option>
                        </select>
                    </div>
                    <div class="action-btns">
                        <button class="btn-new" onclick="openModal()">+ New product</button>
                    </div>
                </div>

                <!-- Product Table -->
                <table class="product-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox"></th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Date added</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <?php
                            try {
                                $query = "SELECT * FROM products ORDER BY id DESC";
                                $stmt = $conn->prepare($query);
                                $stmt->execute();

                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $id = $row['id'];
                                    $status = htmlspecialchars($row['status'] ?? 'Active');
                                    $badgeClass = (strtolower($status) == 'active') ? 'active' : 'out-of-stock';
                                    
                                    $pName = htmlspecialchars($row['product_name']);
                                    $pCat  = htmlspecialchars($row['category']);
                                    $pQty  = (int)$row['quantity'];
                                    $pPrice = (float)$row['price'];

                                    $imageName = !empty($row['image']) ? $row['image'] : 'product_placeholder.png';
                                    $imagePath = "../assets/img/" . $imageName;

                                    echo "<tr>
                                            <td><input type='checkbox'></td>
                                            <td class='product-cell'>
                                                <img src='$imagePath' alt='$pName' style='width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 10px;'>
                                                <span>" . $pName . "</span>
                                            </td>
                                            <td>" . $pCat . "</td>
                                            <td>" . $pQty . "</td>
                                            <td><strong>₱" . number_format($pPrice, 2) . "</strong></td>
                                            <td>" . htmlspecialchars($row['date_added']) . "</td>
                                            <td><span class='badge {$badgeClass}'>" . $status . "</span></td>
                                            <td>
                                                <div class='action-dropdown'>
                                                    <i class='fas fa-ellipsis-h more-icon' style='cursor:pointer; padding: 5px;'></i>
                                                    <div class='dropdown-menu'>
                                                        <a href='javascript:void(0)' onclick=\"openEditModal('$id', '$pName', '$pCat', '$pQty', '$pPrice', '$imagePath')\">
                                                            <i class='fas fa-edit'></i> Edit
                                                        </a>
                                                        <a href='actions/delete_product.php?id=$id' class='text-danger' onclick='return confirm(\"Are you sure?\");'>
                                                            <i class='fas fa-trash'></i> Delete
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='8'>Error: " . $e->getMessage() . "</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div> 
        </div>
    </div>

    <!-- Modal for Adding Product -->
    <div id="addProductModal" class="modal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: #fff; margin: 5% auto; padding: 25px; width: 400px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            <h3 style="margin-top: 0;">Add New Product</h3>
            <hr>
            <form action="../actions/add_product.php" method="POST" enctype="multipart/form-data">
                <div style="margin: 15px 0;">
                    <label>Product Image:</label>
                    <input type="file" name="product_image" accept="image/*" required style="width: 100%; margin-top: 5px;">
                </div>
                <div style="margin: 15px 0;">
                    <label>Product Name:</label>
                    <input type="text" name="product_name" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin: 15px 0;">
                    <label>Category:</label>
                    <select name="category" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="GPU">GPU</option>
                        <option value="CPU">CPU</option>
                        <option value="RAM">RAM</option>
                        <option value="MOTHERBOARD">MOTHERBOARD</option>
                        <option value="HDD">HDD</option>
                        <option value="SSD">SSD</option>
                        <option value="PERIPHERALS">PERIPHERALS</option>
                    </select>
                </div>
                <div style="margin: 15px 0;">
                    <label>Quantity:</label>
                    <input type="number" name="quantity" min="0" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin: 15px 0;">
                    <label>Price (₱):</label>
                    <input type="number" step="0.01" name="price" min="0" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeModal()" style="background: #eee; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Cancel</button>
                    <button type="submit" name="submit" style="background: #2d3436; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Editing Product -->
    <div id="editProductModal" class="modal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: #fff; margin: 5% auto; padding: 25px; width: 400px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            <h3 style="margin-top: 0;">Edit Product</h3>
            <hr>
            <form action="../actions/update_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <div style="text-align: center; margin-bottom: 10px;">
                    <img id="edit_img_preview" src="" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd;">
                </div>
                <div style="margin: 15px 0;">
                    <label>Product Image (Optional):</label>
                    <input type="file" name="product_image" accept="image/*" style="width: 100%; margin-top: 5px;">
                </div>
                <div style="margin: 15px 0;">
                    <label>Product Name:</label>
                    <input type="text" name="product_name" id="edit_name" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin: 15px 0;">
                    <label>Category:</label>
                    <select name="category" id="edit_category" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="GPU">GPU</option>
                        <option value="CPU">CPU</option>
                        <option value="RAM">RAM</option>
                        <option value="MOTHERBOARD">MOTHERBOARD</option>
                        <option value="HDD">HDD</option>
                        <option value="SSD">SSD</option>
                        <option value="PERIPHERALS">PERIPHERALS</option>
                    </select>
                </div>
                <div style="margin: 15px 0;">
                    <label>Quantity:</label>
                    <input type="number" name="quantity" id="edit_quantity" min="0" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin: 15px 0;">
                    <label>Price (₱):</label>
                    <input type="number" step="0.01" name="price" id="edit_price" min="0" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeEditModal()" style="background: #eee; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Cancel</button>
                    <button type="submit" name="update" style="background: #2d3436; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal UI Controls
        function openModal() { document.getElementById('addProductModal').style.display = "block"; }
        function closeModal() { document.getElementById('addProductModal').style.display = "none"; }

        function openEditModal(id, name, category, qty, price, imgPath) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_quantity').value = qty;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_img_preview').src = imgPath;
            document.getElementById('editProductModal').style.display = "block";
        }
        function closeEditModal() { document.getElementById('editProductModal').style.display = "none"; }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                closeModal();
                closeEditModal();
            }
        }

        // --- AJAX Search and Filter Logic ---
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const tableBody = document.getElementById('inventoryTableBody');

        function fetchProducts() {
            const query = searchInput.value;
            const category = categoryFilter.value;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/search_products.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (this.status == 200) {
                    tableBody.innerHTML = this.responseText;
                }
            }
            xhr.send(`query=${encodeURIComponent(query)}&category=${encodeURIComponent(category)}`);
        }

        searchInput.addEventListener('keyup', fetchProducts);
        categoryFilter.addEventListener('change', fetchProducts);
    </script>
</body>
</html>