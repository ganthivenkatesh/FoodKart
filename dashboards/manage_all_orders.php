<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email, r.name as restaurant_name 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          JOIN restaurants r ON o.restaurant_id = r.id 
          WHERE 1=1";
if ($status_filter !== 'all') {
    $query .= " AND o.order_status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY o.created_at DESC";

$orders = $conn->query($query);

// Get statistics
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch_assoc()['count'];
$completed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'delivered'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE payment_status = 'completed'")->fetch_assoc()['total'] ?? 0;
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
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🍴 FoodKart</h2>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Admin Panel</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_users.php">👥 Users</a></li>
                <li><a href="manage_restaurants_admin.php">🍽️ Restaurants</a></li>
                <li><a href="manage_all_orders.php" class="active">📦 All Orders</a></li>
                <li><a href="manage_feedback.php">💬 Feedback</a></li>
                <li><a href="manage_contacts.php">📧 Contact Messages</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1 style="margin-bottom: 2rem;">📦 Manage All Orders</h1>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <a href="manage_all_orders.php?status=all" class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </a>
                
                <a href="manage_all_orders.php?status=pending" class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $pending_orders; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </a>
                
                <a href="manage_all_orders.php?status=delivered" class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $completed_orders; ?></div>
                    <div class="stat-label">Completed Orders</div>
                </a>
                
                <a href="manage_all_orders.php?status=delivered" class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">₹<?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </a>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <strong>Filter by Status:</strong>
                <select class="form-control" onchange="applyFilter()" id="statusFilter">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="out_for_delivery" <?php echo $status_filter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <!-- Orders Table -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem;">All Orders</h2>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Restaurant</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders->num_rows > 0): ?>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                        <small style="color: var(--light-text);"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['restaurant_name']); ?></td>
                                    <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $order['payment_status'] === 'completed' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($order['order_status']) {
                                                'delivered' => 'badge-success',
                                                'cancelled' => 'badge-danger',
                                                default => 'badge-warning'
                                            };
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="../pages/track_order.php?order_id=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline" 
                                           style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">
                                    <p>No orders found matching the filter.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function applyFilter() {
            const status = document.getElementById('statusFilter').value;
            window.location.href = `manage_all_orders.php?status=${status}`;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
