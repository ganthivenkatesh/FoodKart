<?php
require_once '../php/config.php';
requireRole('customer');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: user_home.php');
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get order details
$order_query = "SELECT o.*, r.name as restaurant_name, r.location, r.phone 
                FROM orders o 
                JOIN restaurants r ON o.restaurant_id = r.id 
                WHERE o.id = ? AND o.user_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: user_home.php');
    exit();
}

$order = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .tracking-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 0 20px;
        }
        
        .tracking-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
        }
        
        .order-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #eee;
        }
        
        .tracking-timeline {
            position: relative;
            padding: 2rem 0;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 60px;
            margin-bottom: 2.5rem;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            z-index: 2;
        }
        
        .timeline-item.completed .timeline-icon {
            background-color: var(--success-color);
            color: white;
        }
        
        .timeline-item.active .timeline-icon {
            background-color: var(--primary-color);
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .timeline-line {
            position: absolute;
            left: 22px;
            top: 45px;
            width: 2px;
            height: calc(100% - 45px);
            background-color: #ddd;
        }
        
        .timeline-item.completed .timeline-line {
            background-color: var(--success-color);
        }
        
        .timeline-content h3 {
            margin-bottom: 0.5rem;
            color: var(--dark-text);
        }
        
        .timeline-content p {
            color: var(--light-text);
            margin: 0;
        }
        
        .timeline-time {
            color: var(--light-text);
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-item .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .info-item .label {
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .info-item .value {
            font-weight: bold;
            color: var(--dark-text);
            margin-top: 0.3rem;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            transform: scale(1.1);
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
                <li><a href="menu.php">Menu</a></li>
                <li><a href="user_home.php">Dashboard</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="tracking-container">
        <div class="tracking-card">
            <div class="order-header">
                <h1>🚚 Track Your Order</h1>
                <p style="color: var(--light-text);">Order #<?php echo $order_id; ?></p>
                <h3 style="color: var(--primary-color); margin-top: 0.5rem;">
                    <?php echo htmlspecialchars($order['restaurant_name']); ?>
                </h3>
            </div>
            
            <div class="order-info-grid">
                <div class="info-item">
                    <div class="icon">📦</div>
                    <div class="label">Order Status</div>
                    <div class="value"><?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="icon">💳</div>
                    <div class="label">Payment</div>
                    <div class="value"><?php echo ucfirst($order['payment_status']); ?></div>
                </div>
                <div class="info-item">
                    <div class="icon">💰</div>
                    <div class="label">Total Amount</div>
                    <div class="value">₹<?php echo number_format($order['total_price'], 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="icon">📍</div>
                    <div class="label">Delivery To</div>
                    <div class="value" style="font-size: 0.9rem;"><?php echo htmlspecialchars(substr($order['delivery_address'], 0, 30)) . '...'; ?></div>
                </div>
            </div>
            
            <div class="tracking-timeline">
                <?php
                $statuses = [
                    'placed' => ['icon' => '📝', 'title' => 'Order Placed', 'desc' => 'Your order has been received'],
                    'preparing' => ['icon' => '👨‍🍳', 'title' => 'Preparing', 'desc' => 'Restaurant is preparing your food'],
                    'out_for_delivery' => ['icon' => '🚚', 'title' => 'Out for Delivery', 'desc' => 'Your order is on the way'],
                    'delivered' => ['icon' => '✅', 'title' => 'Delivered', 'desc' => 'Order delivered successfully']
                ];
                
                $current_status = $order['order_status'];
                $status_order = ['placed', 'preparing', 'out_for_delivery', 'delivered'];
                $current_index = array_search($current_status, $status_order);
                
                foreach ($status_order as $index => $status):
                    $status_info = $statuses[$status];
                    $is_completed = $index < $current_index;
                    $is_active = $index === $current_index;
                    $class = $is_completed ? 'completed' : ($is_active ? 'active' : '');
                ?>
                    <div class="timeline-item <?php echo $class; ?>">
                        <?php if ($index < count($status_order) - 1): ?>
                            <div class="timeline-line"></div>
                        <?php endif; ?>
                        <div class="timeline-icon"><?php echo $status_info['icon']; ?></div>
                        <div class="timeline-content">
                            <h3><?php echo $status_info['title']; ?></h3>
                            <p><?php echo $status_info['desc']; ?></p>
                            <?php if ($is_completed || $is_active): ?>
                                <div class="timeline-time">
                                    <?php 
                                    if ($is_active) {
                                        echo 'Updated: ' . date('d M Y, h:i A', strtotime($order['updated_at']));
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($order['order_status'] !== 'delivered' && $order['order_status'] !== 'cancelled'): ?>
                <div class="alert alert-info mt-4">
                    <strong>ℹ️ Estimated Delivery:</strong> 30-45 minutes
                </div>
            <?php endif; ?>
            
            <div class="d-flex gap-2 mt-4">
                <a href="user_home.php" class="btn btn-outline" style="flex: 1; text-align: center;">
                    Back to Orders
                </a>
                <?php if ($order['order_status'] === 'delivered'): ?>
                    <a href="feedback.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary" style="flex: 1; text-align: center;">
                        Rate Order
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <button class="refresh-btn" onclick="location.reload()" title="Refresh Status">
        🔄
    </button>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
<?php $conn->close(); ?>
