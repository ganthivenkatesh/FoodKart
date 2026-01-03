<?php
require_once '../php/config.php';
requireRole('restaurant_owner');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get restaurant info
$rest_query = "SELECT * FROM restaurants WHERE owner_id = ?";
$stmt = $conn->prepare($rest_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant_result = $stmt->get_result();
$restaurant = $restaurant_result->fetch_assoc();

if (!$restaurant) {
    // Redirect to create restaurant page
    header('Location: create_restaurant.php');
    exit();
}

// Check if restaurant is pending approval
if ($restaurant['status'] === 'pending') {
    // Show pending approval page
    include 'restaurant_pending.php';
    exit();
}

// Check if restaurant is rejected
if ($restaurant['status'] === 'rejected') {
    $_SESSION['error'] = 'Your restaurant registration was rejected. Please contact admin for more information.';
    header('Location: create_restaurant.php');
    exit();
}

$restaurant_id = $restaurant['id'];

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN order_status IN ('placed', 'preparing', 'out_for_delivery') THEN 1 ELSE 0 END) as active_orders,
                SUM(CASE WHEN payment_status = 'completed' THEN total_price ELSE 0 END) as total_revenue
                FROM orders WHERE restaurant_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent orders
$orders_query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 WHERE o.restaurant_id = ? 
                 ORDER BY o.created_at DESC 
                 LIMIT 10";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$orders = $stmt->get_result();

// Get menu items count
$menu_count_query = "SELECT COUNT(*) as count FROM menu_items WHERE restaurant_id = ?";
$stmt = $conn->prepare($menu_count_query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$menu_count = $stmt->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard - FoodKart</title>
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
        
        .dashboard-header {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--light-text);
            margin-top: 0.5rem;
        }
        
        .orders-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .order-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 200px;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .order-row:first-child {
            font-weight: bold;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .status-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }
        
        @media (max-width: 968px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
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
                <li><a href="restaurant_dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="manage_menu.php">🍽️ Manage Menu</a></li>
                <li><a href="manage_orders.php">📦 Orders</a></li>
                <li><a href="manage_offers.php">🎉 Offers</a></li>
                <li><a href="restaurant_profile.php">⚙️ Profile</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1>Welcome, <?php echo htmlspecialchars($restaurant['name']); ?>!</h1>
                    <p style="color: var(--light-text); margin: 0.5rem 0;">
                        Status: 
                        <span class="badge <?php echo $restaurant['status'] === 'approved' ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo ucfirst($restaurant['status']); ?>
                        </span>
                        <?php if ($restaurant['is_open']): ?>
                            <span class="badge badge-success">🟢 Open</span>
                        <?php else: ?>
                            <span class="badge badge-danger">🔴 Closed</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <a href="manage_menu.php" class="btn btn-primary">+ Add Menu Item</a>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-value"><?php echo $stats['active_orders'] ?? 0; ?></div>
                    <div class="stat-label">Active Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $stats['completed_orders'] ?? 0; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">₹<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🍽️</div>
                    <div class="stat-value"><?php echo $menu_count; ?></div>
                    <div class="stat-label">Menu Items</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-value"><?php echo number_format($restaurant['rating'], 1); ?></div>
                    <div class="stat-label">Rating</div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="orders-section">
                <h2 style="margin-bottom: 1.5rem;">Recent Orders</h2>
                
                <?php if ($orders->num_rows > 0): ?>
                    <div class="order-row">
                        <div>Order ID</div>
                        <div>Customer</div>
                        <div>Amount</div>
                        <div>Status</div>
                        <div>Actions</div>
                    </div>
                    
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <div class="order-row">
                            <div>#<?php echo $order['id']; ?></div>
                            <div>
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                                <br>
                                <small style="color: var(--light-text);">
                                    <?php echo date('d M, h:i A', strtotime($order['created_at'])); ?>
                                </small>
                            </div>
                            <div>₹<?php echo number_format($order['total_price'], 2); ?></div>
                            <div>
                                <span class="badge <?php 
                                    echo match($order['order_status']) {
                                        'delivered' => 'badge-success',
                                        'cancelled' => 'badge-danger',
                                        'out_for_delivery' => 'badge-veg',
                                        default => 'badge-warning'
                                    };
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?>
                                </span>
                            </div>
                            <div>
                                <?php if ($order['order_status'] !== 'delivered' && $order['order_status'] !== 'cancelled'): ?>
                                    <select class="status-select" onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)">
                                        <option value="">Update Status</option>
                                        <option value="preparing" <?php echo $order['order_status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                        <option value="out_for_delivery" <?php echo $order['order_status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                        <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    </select>
                                <?php else: ?>
                                    <a href="../pages/track_order.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">Track</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="manage_orders.php" class="btn btn-outline">View All Orders</a>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--light-text); padding: 2rem;">
                        No orders yet. Orders will appear here once customers start ordering.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        async function updateOrderStatus(orderId, status) {
            if (!status) return;
            
            try {
                const response = await fetch('../php/update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order_id: orderId, status: status })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Failed to update status: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update order status');
            }
        }
        
        function viewOrder(orderId) {
            window.location.href = 'view_order.php?order_id=' + orderId;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
