<?php
session_start(); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php"); 
    exit(); 
}

// Check if the user is an Admin (Defaulting to false if 'role' isn't set in your session yet)
$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="container">
        <nav class="sidebar">
            <div class="logo-section">
                <div class="logo-circle">
                    <img src="../assets/img/LOGO.png" alt="Logo" class="logo-img">
                </div>
            </div>

            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li class="active"><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li><a href="settings.php"><i class="fas fa-sliders-h"></i> Settings</a></li>
            </ul>

            <div class="logout-section">
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <div class="main-content">
            <div class="product-container">
                <h2>Products</h2>
                
                <div class="toolbar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search products...">
                        
                        <select id="categoryFilter" style="border: none; background: transparent; cursor: pointer; padding: 5px; border-left: 1px solid #ddd; margin-left: 10px;">
                            <option value="">All Categories</option>
                            <option value="GPU">GPU</option>
                            <option value="CPU">CPU</option>
                            <option value="RAM">RAM</option>
                            <option value="MOTHERBOARD">MOTHERBOARD</option>
                            <option value="HDD">HDD</option>
                            <option value="SSD">SSD</option>
                            <option value="PSU">PSU</option>
                            <option value="CASE">CASE</option>
                            <option value="PERIPHERALS">PERIPHERALS</option>
                        </select>
                    </div>
                    <div class="action-btns">
                        <button class="btn-new" onclick="openModal()">+ New product</button>
                    </div>
                </div>

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
                            <th style="text-align: center;">Actions</th>
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

                                    // Only display the delete button if the logged-in user is an Admin
                                    if ($isAdmin) {
                                        echo "      <button type='button' class='action-btn btn-delete' onclick=\"openDeleteModal('$id')\" style='background: #ffebee; color: #c62828; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px;'>
                                                        <i class='fas fa-trash'></i> Delete
                                                    </button>";
                                    }

                                    echo "      </div>
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

   <div id="addProductModal" class="modal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: #fff; margin: 2% auto; padding: 25px; width: 420px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <h3 style="margin-top: 0; color: #333;">Add New Product</h3>
            <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
            
            <form action="../actions/add_product.php" method="POST" enctype="multipart/form-data">
                <div style="text-align: center; margin-bottom: 15px;">
                    <div style="display: inline-block; padding: 5px; border: 1px solid #ddd; border-radius: 8px;">
                        <img id="add_img_preview" src="../assets/img/products/product_placeholder.png" style="width: 100px; height: 100px; object-fit: contain; display: block; border-radius: 4px;">
                    </div>
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Product Image:</label>
                    <input type="file" name="product_image" id="add_image_input" accept="image/*" required style="width: 100%;">
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Product Name:</label>
                    <input type="text" name="product_name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Category:</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="GPU">GPU</option>
                        <option value="CPU">CPU</option>
                        <option value="RAM">RAM</option>
                        <option value="MOTHERBOARD">MOTHERBOARD</option>
                        <option value="HDD">HDD</option>
                        <option value="SSD">SSD</option>
                        <option value="PSU">PSU</option>
                        <option value="CASE">CASE</option>
                        <option value="PERIPHERALS">PERIPHERALS</option>
                    </select>
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Quantity:</label>
                    <input type="number" name="quantity" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Price (₱):</label>
                    <input type="number" step="0.01" name="price" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="text-align: right; margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal()" style="background: #eee; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="submit" name="submit" style="background: #2d3436; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">Save Product</button>
                </div>
            </form>
        </div>
    </div>

   <div id="editProductModal" class="modal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: #fff; margin: 2% auto; padding: 25px; width: 420px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <h3 style="margin-top: 0; color: #333;">Edit Product</h3>
            <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
            
            <form action="../actions/update_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                
                <div style="text-align: center; margin-bottom: 15px;">
                    <div style="display: inline-block; padding: 5px; border: 1px solid #ddd; border-radius: 8px;">
                        <img id="edit_img_preview" src="" style="width: 100px; height: 100px; object-fit: contain; display: block; border-radius: 4px;">
                    </div>
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Product Image (Optional):</label>
                    <input type="file" name="product_image" id="edit_image_input" accept="image/*" style="width: 100%;">
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Product Name:</label>
                    <input type="text" name="product_name" id="edit_name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Category:</label>
                    <select name="category" id="edit_category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="GPU">GPU</option>
                        <option value="CPU">CPU</option>
                        <option value="RAM">RAM</option>
                        <option value="MOTHERBOARD">MOTHERBOARD</option>
                        <option value="HDD">HDD</option>
                        <option value="SSD">SSD</option>
                        <option value="PSU">PSU</option>
                        <option value="CASE">CASE</option>
                        <option value="PERIPHERALS">PERIPHERALS</option>
                    </select>
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Quantity:</label>
                    <input type="number" name="quantity" id="edit_quantity" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="margin: 15px 0;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Price (₱):</label>
                    <input type="number" step="0.01" name="price" id="edit_price" min="0" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="text-align: right; margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeEditModal()" style="background: #eee; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="submit" name="update" style="background: #2d3436; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600;">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteProductModal" class="modal" style="display:none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(3px);">
        <div style="background-color: #fff; margin: 12% auto; padding: 30px; width: 360px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); text-align: center; animation: modalPopIn 0.3s ease;">
            
            <div style="background: #ffebee; color: #d32f2f; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 24px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h3 style="margin: 0 0 10px 0; color: #2d3436; font-size: 20px; font-weight: 700; font-family: sans-serif;">Delete Product?</h3>
            <p style="margin: 0 0 25px 0; color: #636e72; font-size: 14px; line-height: 1.5; font-family: sans-serif;">Are you sure you want to delete this product? This action cannot be undone.</p>
            
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" onclick="closeDeleteModal()" style="flex: 1; background: #f5f6fa; color: #2d3436; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: background 0.2s;">
                    Cancel
                </button>
                <a id="confirmDeleteBtn" href="#" style="flex: 1; text-decoration: none;">
                    <button type="button" style="width: 100%; background: #d32f2f; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: background 0.2s;">
                        Delete
                    </button>
                </a>
            </div>
        </div>
    </div>

    <style>
    @keyframes modalPopIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    </style>

    <script>
        // Modal UI Controls
        function openModal() { document.getElementById('addProductModal').style.display = "block"; }
        function closeModal() { 
            document.getElementById('addProductModal').style.display = "none";
            document.getElementById('add_img_preview').src = "../assets/img/products/product_placeholder.png";
        }

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

        // Custom Delete Modal Controls
        function openDeleteModal(id) {
            document.getElementById('confirmDeleteBtn').href = `actions/delete_product.php?id=${id}`;
            document.getElementById('deleteProductModal').style.display = "block";
        }
        function closeDeleteModal() {
            document.getElementById('deleteProductModal').style.display = "none";
        }

        // Live Image Preview Logic
        function setupImagePreview(inputId, previewId) {
            document.getElementById(inputId).addEventListener('change', function(event) {
                const file = event.target.files[0];
                const preview = document.getElementById(previewId);
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) { preview.src = e.target.result; }
                    reader.readAsDataURL(file);
                }
            });
        }

        setupImagePreview('add_image_input', 'add_img_preview');
        setupImagePreview('edit_image_input', 'edit_img_preview');

        window.onclick = function(event) {
            if (event.target.className === 'modal' || event.target.id === 'deleteProductModal') {
                closeModal();
                closeEditModal();
                closeDeleteModal();
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