<?php
require_once '../php/config.php';
requireRole('restaurant_owner');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get restaurant
$rest_query = "SELECT id, name FROM restaurants WHERE owner_id = ?";
$stmt = $conn->prepare($rest_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

if (!$restaurant) {
    header('Location: create_restaurant.php');
    exit();
}

$restaurant_id = $restaurant['id'];

// Handle add/edit offer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offer_id = isset($_POST['offer_id']) ? intval($_POST['offer_id']) : 0;
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $discount = floatval($_POST['discount']);
    $min_order = floatval($_POST['min_order']);
    $valid_until = sanitize($_POST['valid_until']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($offer_id > 0) {
        // Update existing offer
        $update_query = "UPDATE offers SET title = ?, description = ?, discount = ?, min_order = ?, valid_until = ?, is_active = ? WHERE id = ? AND restaurant_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssddssii", $title, $description, $discount, $min_order, $valid_until, $is_active, $offer_id, $restaurant_id);
    } else {
        // Add new offer
        $insert_query = "INSERT INTO offers (restaurant_id, title, description, discount, min_order, valid_until, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("issddsi", $restaurant_id, $title, $description, $discount, $min_order, $valid_until, $is_active);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = $offer_id > 0 ? 'Offer updated successfully!' : 'Offer added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to save offer.';
    }
    
    header('Location: manage_offers.php');
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_query = "DELETE FROM offers WHERE id = ? AND restaurant_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $delete_id, $restaurant_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Offer deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete offer.';
    }
    
    header('Location: manage_offers.php');
    exit();
}

// Get all offers
$offers_query = "SELECT * FROM offers WHERE restaurant_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($offers_query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$offers = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Offers - FoodKart</title>
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
        
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .offer-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        
        .offer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-color);
        }
        
        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .offer-discount {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .offer-actions {
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
                <li><a href="manage_menu.php">🍽️ Manage Menu</a></li>
                <li><a href="manage_orders.php">📦 Orders</a></li>
                <li><a href="manage_offers.php" class="active">🎉 Offers</a></li>
                <li><a href="restaurant_profile.php">⚙️ Profile</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1>🎉 Manage Offers</h1>
                <button class="btn btn-primary" onclick="showAddModal()">+ Add New Offer</button>
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
            
            <div class="offers-grid">
                <?php if ($offers->num_rows > 0): ?>
                    <?php while ($offer = $offers->fetch_assoc()): ?>
                        <div class="offer-card">
                            <div class="offer-header">
                                <div>
                                    <div class="offer-discount"><?php echo $offer['discount']; ?>% OFF</div>
                                    <h3><?php echo htmlspecialchars($offer['title']); ?></h3>
                                </div>
                                <span class="badge <?php echo $offer['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $offer['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            
                            <p style="color: var(--light-text); margin: 1rem 0;">
                                <?php echo htmlspecialchars($offer['description']); ?>
                            </p>
                            
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                                <p style="margin: 0.5rem 0;">
                                    <strong>Min Order:</strong> ₹<?php echo number_format($offer['min_order'], 2); ?>
                                </p>
                                <p style="margin: 0.5rem 0;">
                                    <strong>Valid Until:</strong> <?php echo date('d M Y', strtotime($offer['valid_until'])); ?>
                                </p>
                            </div>
                            
                            <div class="offer-actions">
                                <button class="btn btn-outline" style="flex: 1;" onclick='editOffer(<?php echo json_encode($offer); ?>)'>Edit</button>
                                <button class="btn btn-outline" style="flex: 1;" onclick="deleteOffer(<?php echo $offer['id']; ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 4rem;">
                        <h3>No offers yet</h3>
                        <p>Create your first offer to attract more customers!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="offerModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Offer</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="offer_id" id="offer_id" value="0">
                
                <div class="form-group">
                    <label for="title">Offer Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="discount">Discount (%)</label>
                    <input type="number" id="discount" name="discount" class="form-control" step="0.01" min="0" max="100" required>
                </div>
                
                <div class="form-group">
                    <label for="min_order">Minimum Order Amount (₹)</label>
                    <input type="number" id="min_order" name="min_order" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="valid_until">Valid Until</label>
                    <input type="date" id="valid_until" name="valid_until" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" id="is_active" checked>
                        Active
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Save Offer</button>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Offer';
            document.getElementById('offer_id').value = '0';
            document.getElementById('title').value = '';
            document.getElementById('description').value = '';
            document.getElementById('discount').value = '';
            document.getElementById('min_order').value = '';
            document.getElementById('valid_until').value = '';
            document.getElementById('is_active').checked = true;
            document.getElementById('offerModal').style.display = 'block';
        }
        
        function editOffer(offer) {
            document.getElementById('modalTitle').textContent = 'Edit Offer';
            document.getElementById('offer_id').value = offer.id;
            document.getElementById('title').value = offer.title;
            document.getElementById('description').value = offer.description;
            document.getElementById('discount').value = offer.discount;
            document.getElementById('min_order').value = offer.min_order;
            document.getElementById('valid_until').value = offer.valid_until;
            document.getElementById('is_active').checked = offer.is_active == 1;
            document.getElementById('offerModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('offerModal').style.display = 'none';
        }
        
        function deleteOffer(id) {
            if (confirm('Are you sure you want to delete this offer?')) {
                window.location.href = 'manage_offers.php?delete=' + id;
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('offerModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
