<?php
require_once '../php/config.php';
requireRole('customer');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user orders
$orders_query = "SELECT o.*, r.name as restaurant_name 
                 FROM orders o 
                 JOIN restaurants r ON o.restaurant_id = r.id 
                 WHERE o.user_id = ? 
                 ORDER BY o.created_at DESC";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();

// Get user stats
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                SUM(total_price) as total_spent
                FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            text-align: center;
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
            margin: 2rem 0;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .order-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="logo">🍴 FoodKart</a>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
                <li><a href="restaurants.php">Restaurants</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="cart.php">🛒 Cart <span id="cartCount" class="badge badge-danger">0</span></a></li>
                <li><a href="user_home.php">Dashboard</a></li>
                <li><a href="../php/auth.php?logout=1" class="btn btn-outline">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1>👋 Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p>Manage your orders and profile</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container" style="padding: 2rem 20px;">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $stats['completed_orders'] ?? 0; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">₹<?php echo number_format($stats['total_spent'] ?? 0, 0); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>
        
        <!-- Orders Section -->
        <div class="orders-section">
            <h2>📋 My Orders</h2>
            
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <h3>Order #<?php echo $order['id']; ?></h3>
                                <p style="color: var(--light-text); margin: 0.3rem 0;">
                                    <?php echo htmlspecialchars($order['restaurant_name']); ?>
                                </p>
                                <small style="color: var(--light-text);">
                                    <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
                                </small>
                            </div>
                            <div style="text-align: right;">
                                <div class="badge <?php 
                                    echo match($order['order_status']) {
                                        'delivered' => 'badge-success',
                                        'cancelled' => 'badge-danger',
                                        'out_for_delivery' => 'badge-veg',
                                        default => 'badge-warning'
                                    };
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <div>
                                <p style="margin: 0;"><strong>Total:</strong> ₹<?php echo number_format($order['total_price'], 2); ?></p>
                                <p style="margin: 0.3rem 0; color: var(--light-text);">
                                    <strong>Payment:</strong> 
                                    <span class="badge <?php echo $order['payment_status'] === 'completed' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="order-actions">
                                <a href="track_order.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                    Track Order
                                </a>
                                
                                <?php if ($order['order_status'] === 'delivered'): ?>
                                    <a href="feedback.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success">
                                        Rate Order
                                    </a>
                                <?php endif; ?>
                                
                                <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>😕 No orders yet</h3>
                    <p>Start ordering delicious food now!</p>
                    <a href="menu.php" class="btn btn-primary mt-3">Browse Menu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../scripts/cart.js"></script>
    <script>
        updateCartCount();
    </script>
</body>
</html>
<?php $conn->close(); ?>
