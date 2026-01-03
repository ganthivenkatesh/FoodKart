<?php
require_once '../php/config.php';
requireRole('restaurant_owner');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get restaurant
$rest_query = "SELECT * FROM restaurants WHERE owner_id = ?";
$stmt = $conn->prepare($rest_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

if (!$restaurant) {
    header('Location: create_restaurant.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $cuisine = sanitize($_POST['cuisine']);
    $location = sanitize($_POST['location']);
    $phone = sanitize($_POST['phone']);
    $description = sanitize($_POST['description']);
    $is_open = isset($_POST['is_open']) ? 1 : 0;
    
    // Handle image upload
    $image = $restaurant['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/';
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'restaurant_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if exists
                if ($restaurant['image'] && file_exists('../' . $restaurant['image'])) {
                    unlink('../' . $restaurant['image']);
                }
                $image = 'assets/images/' . $new_filename;
            }
        }
    }
    
    // Update restaurant
    $update_query = "UPDATE restaurants SET name = ?, cuisine = ?, location = ?, phone = ?, description = ?, image = ?, is_open = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssssiii", $name, $cuisine, $location, $phone, $description, $image, $is_open, $restaurant['id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Profile updated successfully!';
        header('Location: restaurant_profile.php');
        exit();
    } else {
        $_SESSION['error'] = 'Failed to update profile.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Profile - FoodKart</title>
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
        
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: var(--shadow);
        }
        
        .profile-image-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }
        
        .status-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 30px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #4caf50;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
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
                <li><a href="manage_menu.php">🍽️ Manage Menu</a></li>
                <li><a href="manage_orders.php">📦 Orders</a></li>
                <li><a href="manage_offers.php">🎉 Offers</a></li>
                <li><a href="restaurant_profile.php" class="active">⚙️ Profile</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="profile-container">
                <h1 style="margin-bottom: 2rem;">⚙️ Restaurant Profile</h1>
                
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
                
                <div class="profile-card">
                    <div class="profile-header">
                        <?php if ($restaurant['image']): ?>
                            <img src="../<?php echo htmlspecialchars($restaurant['image']); ?>" alt="Restaurant" class="profile-image">
                        <?php else: ?>
                            <div class="profile-image-placeholder">🍽️</div>
                        <?php endif; ?>
                        <div>
                            <h2><?php echo htmlspecialchars($restaurant['name']); ?></h2>
                            <p style="color: var(--light-text);"><?php echo htmlspecialchars($restaurant['cuisine']); ?></p>
                            <span class="badge <?php echo $restaurant['status'] === 'approved' ? 'badge-success' : 'badge-warning'; ?>">
                                <?php echo ucfirst($restaurant['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="status-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" name="is_open" <?php echo $restaurant['is_open'] ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <div>
                                <strong>Restaurant Status</strong>
                                <p style="color: var(--light-text); margin: 0; font-size: 0.9rem;">
                                    Toggle to open/close your restaurant for orders
                                </p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Restaurant Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($restaurant['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cuisine">Cuisine Type</label>
                            <input type="text" id="cuisine" name="cuisine" class="form-control" value="<?php echo htmlspecialchars($restaurant['cuisine']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($restaurant['location']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($restaurant['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($restaurant['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Restaurant Image</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                            <small style="color: var(--light-text);">Leave empty to keep current image</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
