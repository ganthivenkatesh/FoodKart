<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Get statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'],
    'total_restaurants' => $conn->query("SELECT COUNT(*) as count FROM restaurants WHERE status = 'approved'")->fetch_assoc()['count'],
    'total_orders' => $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(total_price) as total FROM orders WHERE payment_status = 'completed'")->fetch_assoc()['total'] ?? 0,
    'pending_restaurants' => $conn->query("SELECT COUNT(*) as count FROM restaurants WHERE status = 'pending'")->fetch_assoc()['count']
];

// Get recent activities
$recent_orders = $conn->query("SELECT o.*, u.name as customer_name, r.name as restaurant_name 
                               FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               JOIN restaurants r ON o.restaurant_id = r.id 
                               ORDER BY o.created_at DESC LIMIT 5");

$pending_restaurants = $conn->query("SELECT r.*, u.name as owner_name, u.email as owner_email 
                                     FROM restaurants r 
                                     JOIN users u ON r.owner_id = u.id 
                                     WHERE r.status = 'pending' 
                                     ORDER BY r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FoodKart</title>
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
        
        .section-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
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
                <li><a href="admin_dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="manage_users.php">👥 Users</a></li>
                <li><a href="manage_restaurants_admin.php">🍽️ Restaurants</a></li>
                <li><a href="manage_all_orders.php">📦 All Orders</a></li>
                <li><a href="manage_feedback.php">💬 Feedback</a></li>
                <li><a href="manage_contacts.php">📧 Contact Messages</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1 style="margin-bottom: 2rem;">👨‍💼 Admin Dashboard</h1>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <a href="manage_users.php?role=customer" class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Customers</div>
                </a>
                
                <a href="manage_restaurants_admin.php?status=approved" class="stat-card">
                    <div class="stat-icon">🍽️</div>
                    <div class="stat-value"><?php echo $stats['total_restaurants']; ?></div>
                    <div class="stat-label">Active Restaurants</div>
                </a>
                
                <a href="manage_all_orders.php" class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </a>
                
                <a href="manage_all_orders.php?status=delivered" class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">₹<?php echo number_format($stats['total_revenue'], 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </a>
                
                <a href="manage_restaurants_admin.php?status=pending" class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $stats['pending_restaurants']; ?></div>
                    <div class="stat-label">Pending Approvals</div>
                </a>
            </div>
            
            <!-- Pending Restaurant Approvals -->
            <?php if ($pending_restaurants->num_rows > 0): ?>
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem;">⏳ Pending Restaurant Approvals</h2>
                
                <?php while ($restaurant = $pending_restaurants->fetch_assoc()): ?>
                    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <h3><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                                <p style="color: var(--light-text); margin: 0.5rem 0;">
                                    <strong>Owner:</strong> <?php echo htmlspecialchars($restaurant['owner_name']); ?> 
                                    (<?php echo htmlspecialchars($restaurant['owner_email']); ?>)
                                </p>
                                <p style="color: var(--light-text); margin: 0.5rem 0;">
                                    <strong>Cuisine:</strong> <?php echo htmlspecialchars($restaurant['cuisine']); ?> | 
                                    <strong>Location:</strong> <?php echo htmlspecialchars($restaurant['location']); ?>
                                </p>
                                <p style="margin: 0.5rem 0;"><?php echo htmlspecialchars($restaurant['description']); ?></p>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-success" onclick="updateRestaurantStatus(<?php echo $restaurant['id']; ?>, 'approved')">
                                    Approve
                                </button>
                                <button class="btn btn-outline" onclick="updateRestaurantStatus(<?php echo $restaurant['id']; ?>, 'rejected')">
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
            
            <!-- Recent Orders -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem;">📦 Recent Orders</h2>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Restaurant</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['restaurant_name']); ?></td>
                                <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
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
                                <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="../pages/track_order.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">Track</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="manage_all_orders.php" class="btn btn-outline">View All Orders</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        async function updateRestaurantStatus(restaurantId, status) {
            if (!confirm(`Are you sure you want to ${status} this restaurant?`)) {
                return;
            }
            
            try {
                const response = await fetch('../php/update_restaurant_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ restaurant_id: restaurantId, status: status })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Failed to update status: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update restaurant status');
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
