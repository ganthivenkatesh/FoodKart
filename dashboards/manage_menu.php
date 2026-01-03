<?php
require_once '../php/config.php';
requireRole('restaurant_owner');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get restaurant
$rest_query = "SELECT id FROM restaurants WHERE owner_id = ?";
$stmt = $conn->prepare($rest_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$restaurant_id = $restaurant['id'];

// Handle add/edit menu item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $name = sanitize($_POST['name']);
    $category = sanitize($_POST['category']);
    $price = floatval($_POST['price']);
    $discount = floatval($_POST['discount']);
    $description = sanitize($_POST['description']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $uploaded_image_name = null;
    $image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
    $is_remote_url = $image_url && (stripos($image_url, 'http://') === 0 || stripos($image_url, 'https://') === 0);
    if ($is_remote_url && strlen($image_url) <= 1024) {
        // Prefer remote URL if provided
        $uploaded_image_name = $image_url;
    } else {
        // Handle image upload if provided
        if (isset($_FILES['image']) && isset($_FILES['image']['error']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_exts = ['jpg','jpeg','png','webp'];
            $max_bytes = 4 * 1024 * 1024; // 4MB
            $tmp = $_FILES['image']['tmp_name'];
            $orig = $_FILES['image']['name'];
            $size = intval($_FILES['image']['size']);
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_exts) && $size > 0 && $size <= $max_bytes) {
                $safe_base = preg_replace('/[^a-zA-Z0-9\-]+/', '-', strtolower(pathinfo($orig, PATHINFO_FILENAME)));
                $new_name = uniqid('fk_', true) . '-' . trim($safe_base,'-') . '.' . $ext;
                $dest_dir = realpath(__DIR__ . '/../assets/images');
                if ($dest_dir === false) {
                    $dest_dir = __DIR__ . '/../assets/images';
                }
                $dest_path = $dest_dir . DIRECTORY_SEPARATOR . $new_name;
                if (@move_uploaded_file($tmp, $dest_path)) {
                    $uploaded_image_name = $new_name;
                }
            }
        }
    }
    
    if ($item_id > 0) {
        // Update existing item (conditionally update image if uploaded)
        if ($uploaded_image_name) {
            $update_query = "UPDATE menu_items SET name = ?, category = ?, price = ?, discount = ?, description = ?, is_available = ?, image = ? WHERE id = ? AND restaurant_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssddsisii", $name, $category, $price, $discount, $description, $is_available, $uploaded_image_name, $item_id, $restaurant_id);
        } else {
            $update_query = "UPDATE menu_items SET name = ?, category = ?, price = ?, discount = ?, description = ?, is_available = ? WHERE id = ? AND restaurant_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssddsiii", $name, $category, $price, $discount, $description, $is_available, $item_id, $restaurant_id);
        }
    } else {
        // Add new item (include image if uploaded)
        if ($uploaded_image_name) {
            $insert_query = "INSERT INTO menu_items (restaurant_id, name, category, price, discount, description, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("issddssi", $restaurant_id, $name, $category, $price, $discount, $description, $uploaded_image_name, $is_available);
        } else {
            $insert_query = "INSERT INTO menu_items (restaurant_id, name, category, price, discount, description, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("issddsi", $restaurant_id, $name, $category, $price, $discount, $description, $is_available);
        }
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = $item_id > 0 ? 'Menu item updated successfully!' : 'Menu item added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to save menu item.';
    }
    
    header('Location: manage_menu.php');
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_query = "DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $delete_id, $restaurant_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Menu item deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete menu item.';
    }
    
    header('Location: manage_menu.php');
    exit();
}

// Get all menu items
$menu_query = "SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($menu_query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$menu_items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .dashboard-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background-color: var(--secondary-color);
            color: white;
            padding: 2rem 0;
        }
        
        .sidebar-header {
            padding: 0 1.5rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: var(--primary-color);
        }
        
        .main-content {
            background-color: var(--light-bg);
            padding: 2rem;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .menu-item-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .item-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🍴 FoodKart</h2>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Restaurant Panel</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="restaurant_dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_menu.php" class="active">🍽️ Manage Menu</a></li>
                <li><a href="manage_orders.php">📦 Orders</a></li>
                <li><a href="manage_offers.php">🎉 Offers</a></li>
                <li><a href="restaurant_profile.php">⚙️ Profile</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1>🍽️ Manage Menu</h1>
                <button class="btn btn-primary" onclick="showAddModal()">+ Add Menu Item</button>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="menu-grid">
                <?php if ($menu_items->num_rows > 0): ?>
                    <?php while ($item = $menu_items->fetch_assoc()): ?>
                        <div class="menu-item-card">
                            <div class="item-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <span class="badge badge-<?php echo $item['category']; ?>">
                                        <?php echo ucfirst($item['category']); ?>
                                    </span>
                                </div>
                                <span class="badge <?php echo $item['is_available'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                </span>
                            </div>
                            
                            <p style="color: var(--light-text); margin: 1rem 0;">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong style="font-size: 1.2rem; color: var(--primary-color);">
                                        ₹<?php echo number_format($item['price'], 2); ?>
                                    </strong>
                                    <?php if ($item['discount'] > 0): ?>
                                        <span class="badge badge-success"><?php echo $item['discount']; ?>% OFF</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="item-actions">
                                <button class="btn btn-outline" style="flex: 1;" onclick='editItem(<?php echo json_encode($item); ?>)'>Edit</button>
                                <button class="btn btn-outline" style="flex: 1;" onclick="deleteItem(<?php echo $item['id']; ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 4rem;">
                        <h3>No menu items yet</h3>
                        <p>Add your first menu item to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Menu Item</h2>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="item_id" value="0">
                
                <div class="form-group">
                    <label for="name">Item Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="veg">Veg</option>
                        <option value="non-veg">Non-Veg</option>
                        <option value="combo">Combo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (₹)</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="discount">Discount (%)</label>
                    <input type="number" id="discount" name="discount" class="form-control" step="0.01" min="0" max="100" value="0">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image_url">Image URL (optional)</label>
                    <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://...">
                    <small style="color: var(--light-text);">Paste a direct image URL (http/https). If provided, it overrides file upload.</small>
                </div>

                <div class="form-group">
                    <label for="image">Image</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    <small style="color: var(--light-text);">JPG/PNG/WEBP, up to 4MB.</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_available" id="is_available" checked>
                        Available for ordering
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Save Item</button>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Menu Item';
            document.getElementById('item_id').value = '0';
            document.getElementById('name').value = '';
            document.getElementById('category').value = 'veg';
            document.getElementById('price').value = '';
            document.getElementById('discount').value = '0';
            document.getElementById('description').value = '';
            document.getElementById('is_available').checked = true;
            document.getElementById('itemModal').style.display = 'block';
        }
        
        function editItem(item) {
            document.getElementById('modalTitle').textContent = 'Edit Menu Item';
            document.getElementById('item_id').value = item.id;
            document.getElementById('name').value = item.name;
            document.getElementById('category').value = item.category;
            document.getElementById('price').value = item.price;
            document.getElementById('discount').value = item.discount;
            document.getElementById('description').value = item.description;
            document.getElementById('is_available').checked = item.is_available == 1;
            document.getElementById('itemModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('itemModal').style.display = 'none';
        }
        
        function deleteItem(id) {
            if (confirm('Are you sure you want to delete this item?')) {
                window.location.href = 'manage_menu.php?delete=' + id;
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('itemModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
