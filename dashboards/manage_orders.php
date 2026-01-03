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

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.restaurant_id = ?";

if ($status_filter !== 'all') {
    $query .= " AND o.order_status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$orders = $stmt->get_result();

// Get statistics
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE restaurant_id = $restaurant_id")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE restaurant_id = $restaurant_id AND order_status IN ('placed', 'confirmed')")->fetch_assoc()['count'];
$active_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE restaurant_id = $restaurant_id AND order_status IN ('preparing', 'out_for_delivery')")->fetch_assoc()['count'];
$completed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE restaurant_id = $restaurant_id AND order_status = 'delivered'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - FoodKart</title>
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
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--light-text);
            margin-top: 0.5rem;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .section-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .order-actions {
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
                <li><a href="manage_orders.php" class="active">📦 Orders</a></li>
                <li><a href="manage_offers.php">🎉 Offers</a></li>
                <li><a href="restaurant_profile.php">⚙️ Profile</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1 style="margin-bottom: 2rem;">📦 Manage Orders</h1>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <a href="manage_orders.php?status=all" class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </a>
                
                <a href="manage_orders.php?status=placed" class="stat-card">
                    <div class="stat-icon">🔔</div>
                    <div class="stat-value"><?php echo $pending_orders; ?></div>
                    <div class="stat-label">New Orders</div>
                </a>
                
                <a href="manage_orders.php?status=preparing" class="stat-card">
                    <div class="stat-icon">👨‍🍳</div>
                    <div class="stat-value"><?php echo $active_orders; ?></div>
                    <div class="stat-label">Active Orders</div>
                </a>
                
                <a href="manage_orders.php?status=delivered" class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $completed_orders; ?></div>
                    <div class="stat-label">Completed</div>
                </a>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <strong>Filter by Status:</strong>
                <select class="form-control" onchange="applyFilter()" id="statusFilter">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                    <option value="placed" <?php echo $status_filter === 'placed' ? 'selected' : ''; ?>>New Orders</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="out_for_delivery" <?php echo $status_filter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <!-- Orders List -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem;">All Orders</h2>
                
                <?php if ($orders->num_rows > 0): ?>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <p style="color: var(--light-text); margin: 0.5rem 0;">
                                        <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                                    </p>
                                    <p style="color: var(--light-text); margin: 0.5rem 0;">
                                        📧 <?php echo htmlspecialchars($order['customer_email']); ?> | 
                                        📱 <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?>
                                    </p>
                                    <p style="color: var(--light-text); margin: 0.5rem 0;">
                                        📍 <?php echo htmlspecialchars($order['delivery_address']); ?>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                        ₹<?php echo number_format($order['total_price'], 2); ?>
                                    </div>
                                    <span class="badge <?php 
                                        echo match($order['order_status']) {
                                            'delivered' => 'badge-success',
                                            'cancelled' => 'badge-danger',
                                            default => 'badge-warning'
                                        };
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?>
                                    </span>
                                    <p style="color: var(--light-text); font-size: 0.9rem; margin-top: 0.5rem;">
                                        <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="order-actions">
                                <?php if ($order['order_status'] === 'placed'): ?>
                                    <button onclick="updateStatus(<?php echo $order['id']; ?>, 'confirmed')" class="btn btn-success">
                                        Accept Order
                                    </button>
                                    <button onclick="updateStatus(<?php echo $order['id']; ?>, 'cancelled')" class="btn btn-outline">
                                        Reject
                                    </button>
                                <?php elseif ($order['order_status'] === 'confirmed'): ?>
                                    <button onclick="updateStatus(<?php echo $order['id']; ?>, 'preparing')" class="btn btn-primary">
                                        Start Preparing
                                    </button>
                                <?php elseif ($order['order_status'] === 'preparing'): ?>
                                    <button onclick="updateStatus(<?php echo $order['id']; ?>, 'out_for_delivery')" class="btn btn-primary">
                                        Ready for Delivery
                                    </button>
                                <?php endif; ?>
                                <a href="../pages/track_order.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem;">
                        <h3>No orders found</h3>
                        <p>Orders will appear here when customers place them.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function applyFilter() {
            const status = document.getElementById('statusFilter').value;
            window.location.href = `manage_orders.php?status=${status}`;
        }
        
        async function updateStatus(orderId, status) {
            const messages = {
                'confirmed': 'Accept this order?',
                'preparing': 'Start preparing this order?',
                'out_for_delivery': 'Mark order as ready for delivery?',
                'cancelled': 'Cancel this order?'
            };
            
            if (!confirm(messages[status])) return;
            
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
    </script>
</body>
</html>
<?php $conn->close(); ?>
